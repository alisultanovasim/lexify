<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  terms:       Array,
  session:     Object,
  deck:        Object,
  wrongTermIds: { type: Array,   default: () => [] },
  isResumed:   { type: Boolean,  default: false },
});

function buildQuestions(terms) {
  const shuffled = [...terms].sort(() => Math.random() - 0.5);
  return shuffled.map((term) => {
    const distractors = terms
      .filter(t => t.id !== term.id)
      .sort(() => Math.random() - 0.5)
      .slice(0, 3)
      .map(t => t.definition);

    const choices = [...distractors, term.definition].sort(() => Math.random() - 0.5);
    return { term, choices, correct: term.definition };
  });
}

const questions    = ref([]);
const currentIndex = ref(0);
const selected     = ref(null);
const submitted    = ref(false);
const completed    = ref(false);
const results      = ref(null);
const startTime    = ref(Date.now());

const resumingWrong = computed(() => props.wrongTermIds.length > 0 && !props.isResumed);

onMounted(() => { questions.value = buildQuestions(props.terms); });

const current  = computed(() => questions.value[currentIndex.value]);
const progress = computed(() => Math.round((currentIndex.value / questions.value.length) * 100));

const choose = async (choice) => {
  if (submitted.value) return;
  selected.value  = choice;
  submitted.value = true;

  const isCorrect = choice === current.value.correct;
  const elapsed   = Date.now() - startTime.value;

  await axios.post(`/study/${props.session.id}/answer`, {
    term_id:          current.value.term.id,
    is_correct:       isCorrect,
    response_time_ms: elapsed,
    rating:           isCorrect ? 4 : 1,
  }).catch(() => {});
};

const next = async () => {
  if (currentIndex.value < questions.value.length - 1) {
    currentIndex.value++;
    selected.value  = null;
    submitted.value = false;
    startTime.value = Date.now();
  } else {
    const res = await axios.post(`/study/${props.session.id}/complete`);
    results.value   = res.data;
    completed.value = true;
  }
};

const choiceClass = (choice) => {
  if (!submitted.value) return 'border-gray-200 text-gray-800 hover:border-cyan-400 hover:bg-cyan-50 cursor-pointer';
  if (choice === current.value.correct) return 'border-green-400 bg-green-50 text-green-800';
  if (choice === selected.value && choice !== current.value.correct) return 'border-red-400 bg-red-50 text-red-800';
  return 'border-gray-200 text-gray-400 cursor-default';
};
</script>

<template>
  <!-- ── Completed ─────────────────────────────────────────────────────── -->
  <div v-if="completed" class="text-center py-16">
    <div class="text-6xl mb-4">🎉</div>
    <h2 class="text-2xl font-bold text-gray-900 mb-2">Test Bitdi!</h2>
    <p class="text-gray-500 mb-2">{{ results?.score }}% düzgün cavab</p>
    <div class="flex items-center justify-center gap-10 mb-8">
      <div class="text-center">
        <p class="text-3xl font-bold text-green-600">{{ results?.correct }}</p>
        <p class="text-sm text-gray-500">Düzgün</p>
      </div>
      <div class="text-center">
        <p class="text-3xl font-bold text-red-500">{{ results?.incorrect }}</p>
        <p class="text-sm text-gray-500">Səhv</p>
      </div>
    </div>
    <div class="flex flex-col sm:flex-row gap-3 justify-center">
      <button v-if="results?.incorrect > 0"
        @click="router.visit(`/decks/${deck.id}/study/test`)"
        class="px-6 py-3 bg-orange-500 text-white rounded-xl hover:bg-orange-600 transition font-medium">
        ⚡ {{ results.incorrect }} Yanlışı Yenidən
      </button>
      <button @click="router.visit(`/decks/${deck.id}/study/test?fresh=1`)"
        class="px-6 py-3 bg-cyan-600 text-white rounded-xl hover:bg-cyan-700 transition font-medium">
        🔄 Yenidən Başla
      </button>
      <button @click="router.visit(`/decks/${deck.id}`)"
        class="px-6 py-3 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50 transition">
        Dəstə Qayıt
      </button>
    </div>
  </div>

  <!-- ── Quiz ──────────────────────────────────────────────────────────── -->
  <div v-else-if="current">

    <!-- Resume / wrong-terms notice -->
    <div v-if="isResumed"
      class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-xl text-sm text-blue-700 flex items-center justify-between">
      <span>▶️ Dayandığınız yerdən davam edirsiniz ({{ terms.length }} sual qalıb)</span>
      <button @click="router.visit(`/decks/${deck.id}/study/test?fresh=1`)"
        class="text-xs px-3 py-1.5 border border-blue-300 text-blue-600 rounded-lg hover:bg-blue-100 transition ml-3 flex-shrink-0">
        Yenidən başla →
      </button>
    </div>
    <div v-else-if="resumingWrong && currentIndex === 0 && !submitted"
      class="mb-4 p-3 bg-orange-50 border border-orange-200 rounded-xl text-sm text-orange-700 flex items-center justify-between">
      <span>⚠️ Keçən sessiyadan <strong>{{ wrongTermIds.length }}</strong> yanlış cavabdan başlayırsınız</span>
      <button @click="router.visit(`/decks/${deck.id}/study/test?fresh=1`)"
        class="text-xs px-3 py-1.5 border border-orange-300 text-orange-600 rounded-lg hover:bg-orange-100 transition ml-3 flex-shrink-0">
        Yenidən başla →
      </button>
    </div>

    <!-- Progress bar -->
    <div class="mb-6">
      <div class="flex justify-between text-sm text-gray-500 mb-2">
        <span>{{ currentIndex + 1 }} / {{ questions.length }}</span>
        <span>{{ progress }}%</span>
      </div>
      <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
        <div class="h-full bg-cyan-500 rounded-full transition-all duration-300" :style="{ width: progress + '%' }"></div>
      </div>
    </div>

    <!-- Question card -->
    <div class="bg-white rounded-2xl border border-gray-200 p-8 mb-6 text-center relative">
      <!-- wrong-term badge -->
      <span v-if="resumingWrong && wrongTermIds.includes(current.term.id)"
        class="absolute top-3 left-3 text-xs bg-orange-100 text-orange-600 px-2 py-0.5 rounded-full">
        keçən dəfə yanlış
      </span>
      <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-3">Tərcüməsini seçin</p>
      <div v-if="current.term.image" class="flex justify-center mb-4">
        <img :src="current.term.image" class="max-h-28 rounded-xl object-cover" />
      </div>
      <div class="flex items-center justify-center gap-2">
        <span v-if="current.term.gender" class="text-sm font-bold px-2 py-0.5 rounded"
          :class="{
            'bg-blue-100 text-blue-700':   current.term.gender === 'der',
            'bg-pink-100 text-pink-700':   current.term.gender === 'die',
            'bg-yellow-100 text-yellow-700': current.term.gender === 'das',
          }">{{ current.term.gender }}</span>
        <p class="text-3xl font-bold text-gray-900">{{ current.term.term }}</p>
      </div>
      <p v-if="current.term.pronunciation" class="text-cyan-500 font-mono text-sm mt-2">
        /{{ current.term.pronunciation }}/
      </p>
    </div>

    <!-- Choices -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
      <button
        v-for="(choice, i) in current.choices"
        :key="i"
        @click="choose(choice)"
        :disabled="submitted"
        class="p-4 rounded-xl border-2 text-left font-medium transition"
        :class="choiceClass(choice)"
      >
        <span class="text-xs font-bold mr-2 opacity-50">{{ ['A','B','C','D'][i] }}</span>
        {{ choice }}
        <span v-if="submitted && choice === current.correct" class="float-right">✓</span>
        <span v-if="submitted && choice === selected && choice !== current.correct" class="float-right">✕</span>
      </button>
    </div>

    <!-- Next button -->
    <button v-if="submitted" @click="next"
      class="w-full py-3 rounded-xl font-medium text-white transition"
      :class="selected === current.correct ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-800 hover:bg-gray-900'">
      {{ currentIndex < questions.length - 1 ? 'Növbəti →' : 'Nəticəyə Bax' }}
    </button>
  </div>
</template>

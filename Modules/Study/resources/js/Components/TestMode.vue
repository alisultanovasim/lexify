<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const props = defineProps({ terms: Array, session: Object, deck: Object });

// Build questions: 4 choices per question (1 correct + 3 distractors)
function buildQuestions(terms) {
  const shuffled = [...terms].sort(() => Math.random() - 0.5);
  return shuffled.map((term) => {
    const distractors = terms
      .filter(t => t.id !== term.id)
      .sort(() => Math.random() - 0.5)
      .slice(0, 3)
      .map(t => t.definition);

    const choices = [...distractors, term.definition]
      .sort(() => Math.random() - 0.5);

    return { term, choices, correct: term.definition };
  });
}

const questions = ref([]);
const currentIndex = ref(0);
const selected = ref(null);
const submitted = ref(false);
const completed = ref(false);
const results = ref(null);
const startTime = ref(Date.now());

onMounted(() => {
  questions.value = buildQuestions(props.terms);
});

const current = computed(() => questions.value[currentIndex.value]);
const progress = computed(() => Math.round((currentIndex.value / questions.value.length) * 100));

const choose = async (choice) => {
  if (submitted.value) return;
  selected.value = choice;
  submitted.value = true;

  const isCorrect = choice === current.value.correct;
  const elapsed = Date.now() - startTime.value;

  await axios.post(`/study/${props.session.id}/answer`, {
    term_id: current.value.term.id,
    is_correct: isCorrect,
    response_time_ms: elapsed,
    rating: isCorrect ? 4 : 1,
  }).catch(() => {});
};

const next = async () => {
  if (currentIndex.value < questions.value.length - 1) {
    currentIndex.value++;
    selected.value = null;
    submitted.value = false;
    startTime.value = Date.now();
  } else {
    const res = await axios.post(`/study/${props.session.id}/complete`);
    results.value = res.data;
    completed.value = true;
  }
};

const choiceClass = (choice) => {
  if (!submitted.value) {
    return 'border-gray-200 text-gray-800 hover:border-indigo-400 hover:bg-indigo-50 cursor-pointer';
  }
  if (choice === current.value.correct) {
    return 'border-green-400 bg-green-50 text-green-800';
  }
  if (choice === selected.value && choice !== current.value.correct) {
    return 'border-red-400 bg-red-50 text-red-800';
  }
  return 'border-gray-200 text-gray-400 cursor-default';
};
</script>

<template>
  <!-- Completed -->
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
    <div class="flex gap-3 justify-center">
      <button @click="router.visit(`/decks/${deck.id}/study/test`)"
        class="px-6 py-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition">Yenidən</button>
      <button @click="router.visit(`/decks/${deck.id}`)"
        class="px-6 py-3 border border-gray-300 rounded-xl hover:bg-gray-50 transition">Dəstə Qayıt</button>
    </div>
  </div>

  <!-- Quiz -->
  <div v-else-if="current">
    <!-- Progress -->
    <div class="mb-6">
      <div class="flex justify-between text-sm text-gray-500 mb-2">
        <span>{{ currentIndex + 1 }} / {{ questions.length }}</span>
        <span>{{ progress }}%</span>
      </div>
      <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
        <div class="h-full bg-indigo-500 rounded-full transition-all duration-300" :style="{ width: progress + '%' }"></div>
      </div>
    </div>

    <!-- Question card -->
    <div class="bg-white rounded-2xl border border-gray-200 p-8 mb-6 text-center">
      <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-3">Tərcüməsini seçin</p>

      <div v-if="current.term.image" class="flex justify-center mb-4">
        <img :src="current.term.image" class="max-h-28 rounded-xl object-cover" />
      </div>

      <div class="flex items-center justify-center gap-2">
        <span v-if="current.term.gender" class="text-sm font-bold px-2 py-0.5 rounded"
          :class="{
            'bg-blue-100 text-blue-700': current.term.gender === 'der',
            'bg-pink-100 text-pink-700': current.term.gender === 'die',
            'bg-yellow-100 text-yellow-700': current.term.gender === 'das',
          }">{{ current.term.gender }}</span>
        <p class="text-3xl font-bold text-gray-900">{{ current.term.term }}</p>
      </div>

      <p v-if="current.term.pronunciation" class="text-indigo-500 font-mono text-sm mt-2">
        /{{ current.term.pronunciation }}/
      </p>
    </div>

    <!-- Choices grid -->
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

    <!-- Next button (only after selecting) -->
    <button
      v-if="submitted"
      @click="next"
      class="w-full py-3 rounded-xl font-medium text-white transition"
      :class="selected === current.correct ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-800 hover:bg-gray-900'"
    >
      {{ currentIndex < questions.length - 1 ? 'Növbəti →' : 'Nəticəyə Bax' }}
    </button>
  </div>
</template>

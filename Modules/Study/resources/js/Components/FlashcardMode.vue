<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import SpeakButton from '@/Components/SpeakButton.vue';

const props = defineProps({
  terms:        Array,
  session:      Object,
  deck:         Object,
  wrongTermIds: { type: Array, default: () => [] },
});

const currentIndex = ref(0);
const flipped      = ref(false);
const completed    = ref(false);
const results      = ref(null);
const startTime    = ref(Date.now());

const current  = computed(() => props.terms[currentIndex.value]);
const progress = computed(() => Math.round((currentIndex.value / props.terms.length) * 100));

// Whether this session was loaded with wrong-terms-first ordering
const resumingWrong = computed(() => props.wrongTermIds.length > 0);

const flip = () => { flipped.value = !flipped.value; };

const answer = async (isCorrect) => {
  const elapsed = Date.now() - startTime.value;
  await axios.post(`/study/${props.session.id}/answer`, {
    term_id:          current.value.id,
    is_correct:       isCorrect,
    response_time_ms: elapsed,
    rating:           isCorrect ? 4 : 1,
  }).catch(() => {});

  if (currentIndex.value < props.terms.length - 1) {
    currentIndex.value++;
    flipped.value  = false;
    startTime.value = Date.now();
  } else {
    const res = await axios.post(`/study/${props.session.id}/complete`);
    results.value   = res.data;
    completed.value = true;
  }
};

const onKeydown = (e) => {
  if (e.code === 'Space')                      { e.preventDefault(); flip(); }
  if (e.code === 'ArrowRight' && flipped.value) answer(true);
  if (e.code === 'ArrowLeft'  && flipped.value) answer(false);
};

onMounted(()  => window.addEventListener('keydown', onKeydown));
onUnmounted(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
  <!-- ── Completed screen ─────────────────────────────────────────────── -->
  <div v-if="completed" class="text-center py-16">
    <div class="text-6xl mb-4">🎉</div>
    <h2 class="text-2xl font-bold text-gray-900 mb-2">Bitdi!</h2>
    <p class="text-gray-500 mb-6">{{ results?.score }}% düzgün cavab</p>

    <div class="flex items-center justify-center gap-8 mb-8">
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
      <!-- If there were wrong answers, offer to redo just those -->
      <button v-if="results?.incorrect > 0"
        @click="router.visit(`/decks/${deck.id}/study/flashcard`)"
        class="px-6 py-3 bg-orange-500 text-white rounded-xl hover:bg-orange-600 transition font-medium">
        ⚡ {{ results.incorrect }} Yanlışı Yenidən
      </button>

      <!-- Fresh start with all terms -->
      <button @click="router.visit(`/decks/${deck.id}/study/flashcard?fresh=1`)"
        class="px-6 py-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition font-medium">
        🔄 Hamısını Yenidən
      </button>

      <button @click="router.visit(`/decks/${deck.id}`)"
        class="px-6 py-3 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50 transition">
        Dəstə Qayıt
      </button>
    </div>
  </div>

  <!-- ── Study screen ──────────────────────────────────────────────────── -->
  <div v-else>

    <!-- Wrong-terms notice (only at start, first card not yet flipped) -->
    <div v-if="resumingWrong && currentIndex === 0 && !flipped"
      class="mb-4 flex items-center justify-between p-3 bg-orange-50 border border-orange-200 rounded-xl">
      <span class="text-sm text-orange-700">
        ⚠️ Keçən sessiyadan <strong>{{ wrongTermIds.length }}</strong> bilmədiyiniz sözdən başlayırsınız
      </span>
      <button @click="router.visit(`/decks/${deck.id}/study/flashcard?fresh=1`)"
        class="ml-3 text-xs px-3 py-1.5 border border-orange-300 text-orange-600 rounded-lg hover:bg-orange-100 transition flex-shrink-0">
        Yenidən başla →
      </button>
    </div>

    <!-- Progress bar -->
    <div class="mb-6">
      <div class="flex justify-between text-sm text-gray-500 mb-2">
        <span>{{ currentIndex + 1 }} / {{ terms.length }}</span>
        <span class="flex items-center gap-2">
          <span v-if="resumingWrong" class="text-orange-500 text-xs">
            {{ wrongTermIds.length }} yanlış söz başda
          </span>
          <span>{{ progress }}%</span>
        </span>
      </div>
      <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
        <div class="h-full bg-indigo-500 rounded-full transition-all duration-300" :style="{ width: progress + '%' }"></div>
      </div>
    </div>

    <!-- Flashcard -->
    <div class="perspective-1000 cursor-pointer mb-6" @click="flip" style="perspective:1000px">
      <div
        class="relative w-full transition-transform duration-500"
        style="transform-style:preserve-3d; min-height:300px;"
        :style="{ transform: flipped ? 'rotateY(180deg)' : 'rotateY(0deg)' }"
      >
        <!-- Front -->
        <div class="absolute inset-0 bg-white rounded-2xl border border-gray-200 shadow-sm flex flex-col items-center justify-center p-8"
          style="backface-visibility:hidden">
          <!-- Wrong-term badge -->
          <span v-if="resumingWrong && wrongTermIds.includes(current?.id)"
            class="absolute top-3 left-3 text-xs bg-orange-100 text-orange-600 px-2 py-0.5 rounded-full font-medium">
            keçən dəfə yanlış
          </span>
          <div v-if="current?.image" class="mb-4">
            <img :src="current.image" class="max-h-32 rounded-xl object-cover" :alt="current?.term" />
          </div>
          <div class="flex items-center gap-2 mb-2">
            <span v-if="current?.gender" class="text-sm font-bold px-2 py-0.5 rounded"
              :class="{
                'bg-blue-100 text-blue-700':   current.gender === 'der',
                'bg-pink-100 text-pink-700':   current.gender === 'die',
                'bg-yellow-100 text-yellow-700': current.gender === 'das',
              }">{{ current?.gender }}</span>
            <span class="text-3xl font-bold text-gray-900">{{ current?.term }}</span>
          </div>
          <p v-if="current?.pronunciation" class="text-indigo-500 font-mono text-sm">/{{ current?.pronunciation }}/</p>
          <div class="flex justify-center mt-3">
            <SpeakButton :text="current?.term" :lang="deck.source_language?.code || 'de'" size="lg" />
          </div>
          <p class="text-gray-400 text-sm mt-3">Boşluğa basın və ya karta toxunun</p>
        </div>

        <!-- Back -->
        <div class="absolute inset-0 bg-indigo-50 rounded-2xl border border-indigo-200 shadow-sm flex flex-col items-center justify-center p-8"
          style="backface-visibility:hidden; transform:rotateY(180deg)">
          <p class="text-3xl font-bold text-indigo-900 mb-3 text-center">{{ current?.definition }}</p>
          <p v-if="current?.notes" class="text-indigo-600 text-sm italic text-center mb-3">{{ current?.notes }}</p>
          <div v-if="current?.examples?.length" class="mt-2 text-center">
            <p class="text-sm text-indigo-700 font-medium">{{ current.examples[0].sentence }}</p>
            <p v-if="current.examples[0].translation" class="text-sm text-indigo-500 mt-0.5">{{ current.examples[0].translation }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Answer buttons (only visible when flipped) -->
    <div v-if="flipped" class="flex gap-4">
      <button @click="answer(false)"
        class="flex-1 py-3 rounded-xl border-2 border-red-200 text-red-600 font-medium hover:bg-red-50 transition flex items-center justify-center gap-2">
        <span>✕</span> Bilmədim
      </button>
      <button @click="answer(true)"
        class="flex-1 py-3 rounded-xl border-2 border-green-200 text-green-600 font-medium hover:bg-green-50 transition flex items-center justify-center gap-2">
        <span>✓</span> Bildim
      </button>
    </div>
    <div v-else class="text-center text-sm text-gray-400 py-3">
      ← Sol: Bilmədim &nbsp;|&nbsp; Boşluq: Çevir &nbsp;|&nbsp; Sağ: Bildim →
    </div>

  </div>
</template>

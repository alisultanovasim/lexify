<script setup>
import { ref, computed, nextTick } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import SpeakButton from '@/Components/SpeakButton.vue';

const props = defineProps({
  terms:       Array,
  session:     Object,
  deck:        Object,
  wrongTermIds: { type: Array, default: () => [] },
});

const queue        = ref([...props.terms]);
const currentIndex = ref(0);
const userAnswer   = ref('');
const submitted    = ref(false);
const isCorrect    = ref(false);
const wrongQueue   = ref([]);
const round        = ref(1);
const completed    = ref(false);
const results      = ref(null);
const inputRef     = ref(null);

const correctTermIds  = ref(new Set());
const resumingWrong   = computed(() => props.wrongTermIds.length > 0);

const current  = computed(() => queue.value[currentIndex.value]);
const progress = computed(() =>
  props.terms.length ? Math.round((correctTermIds.value.size / props.terms.length) * 100) : 0,
);

// ── Levenshtein fuzzy match ───────────────────────────────────────────────
function levenshtein(a, b) {
  const dp = [];
  for (let i = 0; i <= a.length; i++) {
    dp[i] = [i];
    for (let j = 1; j <= b.length; j++) {
      dp[i][j] = i === 0 ? j
        : a[i-1] === b[j-1] ? dp[i-1][j-1]
        : 1 + Math.min(dp[i-1][j], dp[i][j-1], dp[i-1][j-1]);
    }
  }
  return dp[a.length][b.length];
}

const normStr = (s) => s.trim().toLowerCase().replace(/[.,!?;()/\\-]/g, '').replace(/\s+/g, ' ');

const isAnswerCorrect = (userInput, definition) => {
  const userNorm = normStr(userInput);
  if (!userNorm) return false;
  const parts = definition.split(/[,;\/]/).map(p => normStr(p)).filter(p => p.length > 0);
  for (const part of parts) {
    if (userNorm === part) return true;
    const maxDist = part.length <= 3 ? 0 : part.length <= 6 ? 1 : part.length <= 10 ? 2 : 3;
    if (levenshtein(userNorm, part) <= maxDist) return true;
  }
  return false;
};

// ── Queue movement (shared by next + skip) ───────────────────────────────
const advanceQueue = async () => {
  if (currentIndex.value < queue.value.length - 1) {
    currentIndex.value++;
    userAnswer.value = '';
    submitted.value  = false;
    isCorrect.value  = false;
    await nextTick();
    inputRef.value?.focus();
  } else {
    if (wrongQueue.value.length === 0) {
      const res = await axios.post(`/study/${props.session.id}/complete`);
      results.value   = res.data;
      completed.value = true;
    } else {
      round.value++;
      queue.value      = [...wrongQueue.value];
      wrongQueue.value = [];
      currentIndex.value = 0;
      userAnswer.value   = '';
      submitted.value    = false;
      isCorrect.value    = false;
      await nextTick();
      inputRef.value?.focus();
    }
  }
};

// Submit answer
const submit = async () => {
  if (!userAnswer.value.trim()) return;
  submitted.value = true;
  isCorrect.value = isAnswerCorrect(userAnswer.value, current.value.definition);

  if (isCorrect.value) {
    correctTermIds.value = new Set([...correctTermIds.value, current.value.id]);
  }

  await axios.post(`/study/${props.session.id}/answer`, {
    term_id:    current.value.id,
    is_correct: isCorrect.value,
    rating:     isCorrect.value ? 4 : 1,
  }).catch(() => {});
};

// After seeing the feedback, move to next card
const next = async () => {
  if (!isCorrect.value) wrongQueue.value.push(current.value);
  await advanceQueue();
};

// Skip: immediately move on, treat as incorrect (will reappear next round)
const skip = async () => {
  wrongQueue.value.push(current.value);
  await axios.post(`/study/${props.session.id}/answer`, {
    term_id:    current.value.id,
    is_correct: false,
    rating:     1,
  }).catch(() => {});
  await advanceQueue();
};
</script>

<template>
  <!-- ── Completed ─────────────────────────────────────────────────────── -->
  <div v-if="completed" class="text-center py-16">
    <div class="text-6xl mb-4">🏆</div>
    <h2 class="text-2xl font-bold text-gray-900 mb-2">Mükəmməl!</h2>
    <p class="text-gray-500 mb-2">Bütün sözləri öyrəndiniz!</p>
    <p class="text-gray-400 text-sm mb-8">{{ round }} tur, {{ results?.correct }} düzgün</p>
    <div class="flex gap-3 justify-center">
      <button @click="router.visit(`/decks/${deck.id}/study/learn`)"
        class="px-6 py-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition">Yenidən</button>
      <button @click="router.visit(`/decks/${deck.id}`)"
        class="px-6 py-3 border border-gray-300 rounded-xl hover:bg-gray-50 transition">Dəstə Qayıt</button>
    </div>
  </div>

  <!-- ── Study ─────────────────────────────────────────────────────────── -->
  <div v-else>

    <!-- Wrong-terms notice (first card, not yet answered) -->
    <div v-if="resumingWrong && round === 1 && currentIndex === 0 && !submitted"
      class="mb-4 p-3 bg-orange-50 border border-orange-200 rounded-xl text-sm text-orange-700 flex items-center justify-between">
      <span>⚠️ Keçən sessiyadan <strong>{{ wrongTermIds.length }}</strong> bilmədiyiniz sözdən başlayırsınız</span>
    </div>

    <!-- Progress bar -->
    <div class="mb-4">
      <div class="flex justify-between text-sm text-gray-500 mb-2">
        <span>Tur {{ round }} — {{ currentIndex + 1 }}/{{ queue.length }}</span>
        <span class="flex items-center gap-2">
          <span v-if="wrongQueue.length > 0" class="text-orange-500">{{ wrongQueue.length }} yanlış</span>
          <span :class="progress >= 100 ? 'text-green-600 font-semibold' : 'text-gray-400'">
            {{ progress }}%<span v-if="progress >= 100"> ✓</span>
          </span>
        </span>
      </div>
      <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
        <div class="h-full bg-indigo-500 rounded-full transition-all" :style="{ width: progress + '%' }"></div>
      </div>
    </div>

    <!-- Card -->
    <div class="bg-white rounded-2xl border border-gray-200 p-8 mb-6 text-center relative">
      <!-- "keçən dəfə yanlış" badge -->
      <span v-if="resumingWrong && wrongTermIds.includes(current?.id)"
        class="absolute top-3 left-3 text-xs bg-orange-100 text-orange-600 px-2 py-0.5 rounded-full">
        keçən dəfə yanlış
      </span>

      <div v-if="current?.image" class="flex justify-center mb-4">
        <img :src="current.image" class="max-h-28 rounded-xl object-cover" />
      </div>
      <div class="flex items-center justify-center gap-2 mb-2">
        <span v-if="current?.gender" class="text-sm font-bold px-2 py-0.5 rounded"
          :class="{
            'bg-blue-100 text-blue-700':     current.gender === 'der',
            'bg-pink-100 text-pink-700':     current.gender === 'die',
            'bg-yellow-100 text-yellow-700': current.gender === 'das',
          }">{{ current?.gender }}</span>
        <div class="flex items-center justify-center gap-2">
          <p class="text-3xl font-bold text-gray-900">{{ current?.term }}</p>
          <SpeakButton v-if="current?.term" :text="current.term" :lang="deck.source_language?.code || 'de'" size="lg" />
        </div>
      </div>
      <p v-if="current?.pronunciation" class="text-indigo-500 font-mono text-sm">/{{ current.pronunciation }}/</p>
      <p v-if="current?.notes && submitted" class="text-gray-500 text-sm italic mt-2">{{ current.notes }}</p>
    </div>

    <!-- Input -->
    <div class="mb-4">
      <input
        ref="inputRef"
        v-model="userAnswer"
        type="text"
        placeholder="Tərcüməni yazın..."
        :disabled="submitted"
        :class="[
          'w-full px-4 py-3 text-lg border-2 rounded-xl outline-none transition',
          submitted
            ? isCorrect ? 'border-green-400 bg-green-50 text-green-800' : 'border-red-400 bg-red-50 text-red-800'
            : 'border-gray-300 focus:border-indigo-500'
        ]"
        @keyup.enter="submitted ? next() : submit()"
        autofocus
      />
    </div>

    <!-- Wrong answer feedback -->
    <div v-if="submitted && !isCorrect" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl">
      <p class="text-red-600 text-sm font-medium">Düzgün cavab:</p>
      <p class="text-red-800 text-lg font-bold">{{ current?.definition }}</p>
      <p v-if="current?.examples?.length" class="text-red-600 text-sm mt-1 italic">
        {{ current.examples[0].sentence }}
      </p>
    </div>

    <!-- Action buttons -->
    <div class="flex gap-3">
      <template v-if="!submitted">
        <button @click="submit" :disabled="!userAnswer.trim()"
          class="flex-1 py-3 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 disabled:opacity-50 transition">
          Yoxla
        </button>
        <button @click="skip"
          class="py-3 px-5 border border-gray-300 text-gray-500 rounded-xl font-medium hover:bg-gray-50 transition">
          Keç
        </button>
      </template>
      <button v-else @click="next"
        :class="['flex-1 py-3 rounded-xl font-medium text-white transition',
          isCorrect ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-800 hover:bg-gray-900']">
        {{ currentIndex < queue.length - 1
            ? 'Növbəti →'
            : (wrongQueue.length > 0 ? `${wrongQueue.length} yanlışa qayıt →` : 'Bitir') }}
      </button>
    </div>
  </div>
</template>

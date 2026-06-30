<script setup>
import { ref, computed, nextTick } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import SpeakButton from '@/Components/SpeakButton.vue';

const props = defineProps({ terms: Array, session: Object, deck: Object });

const currentIndex = ref(0);
const userAnswer = ref('');
const submitted = ref(false);
const isCorrect = ref(false);
const completed = ref(false);
const results = ref(null);
const inputRef = ref(null);

const current = computed(() => props.terms[currentIndex.value]);
const progress = computed(() => Math.round((currentIndex.value / props.terms.length) * 100));

// Levenshtein distance for typo tolerance
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

  // Split definition by , ; / — support multiple valid answers
  const parts = definition.split(/[,;\/]/).map(p => normStr(p)).filter(p => p.length > 0);

  for (const part of parts) {
    // Exact match
    if (userNorm === part) return true;
    // Fuzzy: allow typos proportional to word length
    const maxDist = part.length <= 3 ? 0 : part.length <= 6 ? 1 : part.length <= 10 ? 2 : 3;
    if (levenshtein(userNorm, part) <= maxDist) return true;
  }
  return false;
};

const submit = async () => {
  if (!userAnswer.value.trim()) return;
  submitted.value = true;
  isCorrect.value = isAnswerCorrect(userAnswer.value, current.value.definition);

  await axios.post(`/study/${props.session.id}/answer`, {
    term_id: current.value.id,
    is_correct: isCorrect.value,
    rating: isCorrect.value ? 5 : 1,
  }).catch(() => {});
};

const next = async () => {
  if (currentIndex.value < props.terms.length - 1) {
    currentIndex.value++;
    userAnswer.value = '';
    submitted.value = false;
    isCorrect.value = false;
    await nextTick();
    inputRef.value?.focus();
  } else {
    const res = await axios.post(`/study/${props.session.id}/complete`);
    results.value = res.data;
    completed.value = true;
  }
};
</script>

<template>
  <div v-if="completed" class="text-center py-16">
    <div class="text-6xl mb-4">🎉</div>
    <h2 class="text-2xl font-bold text-gray-900 mb-2">Bitdi!</h2>
    <p class="text-gray-500 mb-8">{{ results?.score }}% düzgün cavab</p>
    <div class="flex gap-3 justify-center">
      <button @click="router.visit(`/decks/${deck.id}/study/write`)"
        class="px-6 py-3 bg-cyan-600 text-white rounded-xl hover:bg-cyan-700 transition">Yenidən</button>
      <button @click="router.visit(`/decks/${deck.id}`)"
        class="px-6 py-3 border border-gray-300 rounded-xl hover:bg-gray-50 transition">Dəstə Qayıt</button>
    </div>
  </div>

  <div v-else>
    <!-- Progress -->
    <div class="mb-6">
      <div class="flex justify-between text-sm text-gray-500 mb-2">
        <span>{{ currentIndex + 1 }} / {{ terms.length }}</span>
        <span>{{ progress }}%</span>
      </div>
      <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
        <div class="h-full bg-cyan-500 rounded-full transition-all" :style="{ width: progress + '%' }"></div>
      </div>
    </div>

    <!-- Card -->
    <div class="bg-white rounded-2xl border border-gray-200 p-8 mb-6 text-center">
      <div v-if="current.image" class="mb-4 flex justify-center">
        <img :src="current.image" class="max-h-32 rounded-xl object-cover" />
      </div>
      <div class="flex items-center justify-center gap-2 mb-2">
        <span v-if="current.gender" class="text-sm font-bold px-2 py-0.5 rounded"
          :class="{
            'bg-blue-100 text-blue-700': current.gender === 'der',
            'bg-pink-100 text-pink-700': current.gender === 'die',
            'bg-yellow-100 text-yellow-700': current.gender === 'das',
          }">{{ current.gender }}</span>
        <div class="flex items-center justify-center gap-2">
          <p class="text-3xl font-bold text-gray-900">{{ current.term }}</p>
          <SpeakButton :text="current.term" :lang="deck.source_language?.code || 'de'" size="lg" />
        </div>
      </div>
      <p v-if="current.pronunciation" class="text-cyan-500 font-mono text-sm mb-2">/{{ current.pronunciation }}/</p>
      <p class="text-gray-500 text-sm">Tərcüməni yazın</p>
    </div>

    <!-- Input -->
    <div class="mb-4">
      <input
        ref="inputRef"
        v-model="userAnswer"
        type="text"
        placeholder="Cavabınızı yazın..."
        :disabled="submitted"
        :class="[
          'w-full px-4 py-3 text-lg border-2 rounded-xl outline-none transition',
          submitted
            ? isCorrect ? 'border-green-400 bg-green-50 text-green-800' : 'border-red-400 bg-red-50 text-red-800'
            : 'border-gray-300 focus:border-cyan-500'
        ]"
        @keyup.enter="submitted ? next() : submit()"
        autofocus
      />
    </div>

    <!-- Feedback -->
    <div v-if="submitted && !isCorrect" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-sm">
      <p class="text-red-600 font-medium">Düzgün cavab:</p>
      <p class="text-red-800 text-lg font-bold">{{ current.definition }}</p>
    </div>

    <!-- Buttons -->
    <div class="flex gap-3">
      <button v-if="!submitted" @click="submit" :disabled="!userAnswer.trim()"
        class="flex-1 py-3 bg-cyan-600 text-white rounded-xl font-medium hover:bg-cyan-700 disabled:opacity-50 transition">
        Yoxla
      </button>
      <button v-else @click="next"
        class="flex-1 py-3 rounded-xl font-medium transition"
        :class="isCorrect ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-gray-800 hover:bg-gray-900 text-white'">
        {{ currentIndex < terms.length - 1 ? 'Növbəti →' : 'Bitir' }}
      </button>
    </div>
  </div>
</template>

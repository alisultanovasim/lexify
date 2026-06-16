<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import FlashcardMode from '../../Components/FlashcardMode.vue';
import MatchMode from '../../Components/MatchMode.vue';
import LearnMode from '../../Components/LearnMode.vue';
import TestMode from '../../Components/TestMode.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  deck:          Object,
  terms:         Array,
  mode:          String,
  session:       Object,
  wrongTermIds:  { type: Array,   default: () => [] },
  matchProgress: { type: Object,  default: null },
  isResumed:     { type: Boolean, default: false },
});

const modeLabels = {
  flashcard: 'Kartlar',
  learn:     'Öyrən',
  match:     'Uyğunlaş',
  test:      'Test',
};
</script>

<template>
  <AppLayout :title="`${deck.title} - ${modeLabels[mode] || mode}`">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <button @click="router.visit(`/decks/${deck.id}`)" class="text-gray-400 hover:text-gray-600 transition">←</button>
          <div>
            <h1 class="font-semibold text-gray-900">{{ deck.title }}</h1>
            <p class="text-sm text-gray-500">{{ modeLabels[mode] || mode }} · {{ terms.length }} söz</p>
          </div>
        </div>
      </div>

      <!-- Mode components -->
      <FlashcardMode v-if="mode === 'flashcard'" :terms="terms" :session="session" :deck="deck" :wrong-term-ids="wrongTermIds" />
      <LearnMode     v-else-if="mode === 'learn'" :terms="terms" :session="session" :deck="deck" :wrong-term-ids="wrongTermIds" />
      <MatchMode     v-else-if="mode === 'match'" :terms="terms" :session="session" :deck="deck" :match-progress="matchProgress" />
      <TestMode      v-else-if="mode === 'test'"  :terms="terms" :session="session" :deck="deck" :wrong-term-ids="wrongTermIds" :is-resumed="isResumed" />

      <!-- Fallback -->
      <div v-else class="text-center py-20">
        <button @click="router.visit(`/decks/${deck.id}`)"
          class="px-6 py-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition">
          ← Dəstə Qayıt
        </button>
      </div>

    </div>
  </AppLayout>
</template>

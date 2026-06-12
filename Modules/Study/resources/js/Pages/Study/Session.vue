<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import FlashcardMode from '../../Components/FlashcardMode.vue';
import WriteMode from '../../Components/WriteMode.vue';
import MatchMode from '../../Components/MatchMode.vue';
import LearnMode from '../../Components/LearnMode.vue';
import TestMode from '../../Components/TestMode.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  deck: Object,
  terms: Array,
  mode: String,
  session: Object,
});

const modeLabels = {
  flashcard: 'Kartlar',
  learn: 'Öyrən',
  write: 'Yaz',
  match: 'Uyğunlaş',
  test: 'Test',
};
</script>

<template>
  <AppLayout :title="`${deck.title} - ${modeLabels[mode]}`">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <Link :href="`/decks/${deck.id}`" class="text-gray-400 hover:text-gray-600 transition">←</Link>
          <div>
            <h1 class="font-semibold text-gray-900">{{ deck.title }}</h1>
            <p class="text-sm text-gray-500">{{ modeLabels[mode] }} · {{ terms.length }} söz</p>
          </div>
        </div>
      </div>

      <!-- Mode components -->
      <FlashcardMode v-if="mode === 'flashcard'" :terms="terms" :session="session" :deck="deck" />
      <WriteMode v-else-if="mode === 'write'" :terms="terms" :session="session" :deck="deck" />
      <MatchMode v-else-if="mode === 'match'" :terms="terms" :session="session" :deck="deck" />
      <LearnMode v-else-if="mode === 'learn'" :terms="terms" :session="session" :deck="deck" />
      <TestMode v-else-if="mode === 'test'" :terms="terms" :session="session" :deck="deck" />

      <!-- Coming soon for others -->
      <div v-else class="text-center py-20">
        <div class="text-6xl mb-4">🚧</div>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ modeLabels[mode] }} rejimi</h3>
        <p class="text-gray-500 mb-6">Bu rejim tezliklə əlavə ediləcək</p>
        <Link :href="`/decks/${deck.id}`" class="px-6 py-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition">
          Geri Qayıt
        </Link>
      </div>

    </div>
  </AppLayout>
</template>

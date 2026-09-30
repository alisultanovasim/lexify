<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import LearnMode from '../../Components/LearnMode.vue';
import MatchMode from '../../Components/MatchMode.vue';
import TestMode  from '../../Components/TestMode.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  terms:     Array,
  mode:      String,
  session:   Object,
  deckCount: Number,
});

const modeLabels = { learn: 'Öyrən', match: 'Uyğunlaş', test: 'Test' };

const virtualDeck = {
  id: null,
  title: 'Ümumi sinaq',
  source_language: { code: 'de' },
  target_language: { code: 'az' },
};
</script>

<template>
  <AppLayout :title="`Ümumi sinaq — ${modeLabels[mode] ?? mode}`">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

      <!-- Header -->
      <div class="flex items-center gap-3 mb-6">
        <button @click="router.visit('/decks')" class="text-gray-400 hover:text-gray-600 transition">←</button>
        <div>
          <h1 class="font-semibold text-gray-900">Ümumi sinaq</h1>
          <p class="text-sm text-gray-500">{{ modeLabels[mode] ?? mode }} · {{ deckCount }} dəst · {{ terms.length }} söz</p>
        </div>
      </div>

      <!-- Mode components -->
      <LearnMode
        v-if="mode === 'learn'"
        :terms="terms"
        :session="session"
        :deck="virtualDeck"
        exit-url="/decks"
      />
      <MatchMode
        v-else-if="mode === 'match'"
        :terms="terms"
        :session="session"
        :deck="virtualDeck"
        exit-url="/decks"
      />
      <TestMode
        v-else-if="mode === 'test'"
        :terms="terms"
        :session="session"
        :deck="virtualDeck"
        exit-url="/decks"
      />

    </div>
  </AppLayout>
</template>

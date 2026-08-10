<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ decks: Array });

const deckColors = ['#6366f1','#ec4899','#f59e0b','#10b981','#3b82f6','#8b5cf6'];

const sortBy = ref('newest');

const sortOptions = [
    { value: 'newest',     label: 'Ən yeni' },
    { value: 'oldest',     label: 'Ən köhnə' },
    { value: 'name_asc',   label: 'Ad (A→Z)' },
    { value: 'name_desc',  label: 'Ad (Z→A)' },
    { value: 'words_desc', label: 'Söz sayı (çox→az)' },
    { value: 'words_asc',  label: 'Söz sayı (az→çox)' },
];

const sortedDecks = computed(() => {
    const list = [...props.decks];
    switch (sortBy.value) {
        case 'oldest':     return list.sort((a, b) => a.id - b.id);
        case 'name_asc':   return list.sort((a, b) => a.title.localeCompare(b.title));
        case 'name_desc':  return list.sort((a, b) => b.title.localeCompare(a.title));
        case 'words_desc': return list.sort((a, b) => b.terms_count - a.terms_count);
        case 'words_asc':  return list.sort((a, b) => a.terms_count - b.terms_count);
        default:           return list.sort((a, b) => b.id - a.id);
    }
});
</script>

<template>
  <AppLayout title="Dəstlər">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Mənim Dəstlərim</h1>
          <p class="text-gray-500 text-sm mt-1">{{ decks.length }} dəst</p>
        </div>
        <div class="flex items-center gap-2">
          <select
            v-model="sortBy"
            class="px-3 py-2 text-sm border border-gray-200 rounded-xl bg-white text-gray-700 focus:ring-2 focus:ring-cyan-500 outline-none"
          >
            <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
          <button
            @click="router.visit('/decks/create')"
            class="inline-flex items-center gap-2 px-4 py-2 bg-cyan-600 text-white rounded-xl font-medium hover:bg-cyan-700 transition"
          >
            <span class="text-lg">+</span>
            Yeni Dəst
          </button>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="decks.length === 0" class="text-center py-20">
        <div class="text-6xl mb-4">📚</div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Hələ dəst yoxdur</h3>
        <p class="text-gray-500 mb-6">İlk söz dəstinizi yaradın</p>
        <button @click="router.visit('/decks/create')" class="px-6 py-3 bg-cyan-600 text-white rounded-xl font-medium hover:bg-cyan-700 transition">
          Dəst Yarat
        </button>
      </div>

      <!-- Decks Grid -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <button
          v-for="deck in sortedDecks"
          :key="deck.id"
          @click="router.visit(`/decks/${deck.id}`)"
          class="group bg-white rounded-2xl border border-gray-200 hover:border-cyan-300 hover:shadow-md transition-all p-5 block text-left w-full"
        >
          <div class="flex items-start justify-between mb-4">
            <div
              class="w-12 h-12 rounded-xl flex items-center justify-center text-white text-xl font-bold"
              :style="{ background: deck.color || '#6366f1' }"
            >
              {{ deck.title[0]?.toUpperCase() }}
            </div>
            <div class="flex items-center gap-1 text-xs text-gray-500">
              <span v-if="deck.source_language">{{ deck.source_language.flag_emoji }}</span>
              <span v-if="deck.source_language && deck.target_language">→</span>
              <span v-if="deck.target_language">{{ deck.target_language.flag_emoji }}</span>
            </div>
          </div>

          <h3 class="font-semibold text-gray-900 group-hover:text-cyan-600 transition line-clamp-2">
            {{ deck.title }}
          </h3>
          <p v-if="deck.description" class="text-sm text-gray-500 mt-1 line-clamp-2">{{ deck.description }}</p>

          <div class="mt-4 flex items-center justify-between">
            <span class="text-sm font-medium text-gray-600">{{ deck.terms_count }} söz</span>
            <span v-if="deck.is_public" class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">İctimai</span>
          </div>
        </button>
      </div>
    </div>
  </AppLayout>
</template>

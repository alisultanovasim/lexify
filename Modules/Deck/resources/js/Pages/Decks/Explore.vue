<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
  decks: Object,
  languages: Array,
  filters: Object,
});

const search = ref(props.filters?.search || '');
const lang = ref(props.filters?.lang || '');

let searchTimer;
watch([search, lang], () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    router.get('/explore', { search: search.value, lang: lang.value }, {
      preserveState: true,
      replace: true,
    });
  }, 400);
});

const cloneDeck = (deckId) => {
  router.post(`/decks/${deckId}/clone`, {}, {
    preserveScroll: true,
  });
};
</script>

<template>
  <AppLayout title="Kəşfet">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Kəşfet</h1>
          <p class="text-gray-500 text-sm mt-1">İctimai söz dəstlərini tapmaq</p>
        </div>
        <button @click="router.visit('/decks')" class="text-sm text-gray-500 hover:text-indigo-600 transition">← Dəstlərim</button>
      </div>

      <!-- Filters -->
      <div class="flex gap-3 mb-6">
        <div class="relative flex-1 max-w-sm">
          <input
            v-model="search"
            type="text"
            placeholder="Dəst axtar..."
            class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
          />
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔍</span>
        </div>
        <select
          v-model="lang"
          class="px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
        >
          <option value="">Bütün dillər</option>
          <option v-for="l in languages" :key="l.code" :value="l.code">
            {{ l.flag_emoji }} {{ l.native_name }}
          </option>
        </select>
      </div>

      <!-- Empty state -->
      <div v-if="decks.data.length === 0" class="text-center py-20">
        <div class="text-6xl mb-4">🔍</div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Dəst tapılmadı</h3>
        <p class="text-gray-500">Axtarış şərtlərini dəyişin</p>
      </div>

      <!-- Decks grid -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        <div
          v-for="deck in decks.data"
          :key="deck.id"
          class="bg-white rounded-2xl border border-gray-200 hover:border-indigo-300 hover:shadow-md transition p-5"
        >
          <div class="flex items-start justify-between mb-4">
            <div
              class="w-12 h-12 rounded-xl flex items-center justify-center text-white text-xl font-bold"
              :style="{ background: deck.color || '#6366f1' }"
            >
              {{ deck.title[0]?.toUpperCase() }}
            </div>
            <div class="text-right text-xs text-gray-400">
              <p>{{ deck.user?.name }}</p>
              <div class="flex items-center gap-1 justify-end mt-1">
                <span v-if="deck.source_language">{{ deck.source_language.flag_emoji }}</span>
                <span v-if="deck.source_language && deck.target_language">→</span>
                <span v-if="deck.target_language">{{ deck.target_language.flag_emoji }}</span>
              </div>
            </div>
          </div>

          <h3 class="font-semibold text-gray-900 line-clamp-2 mb-1">{{ deck.title }}</h3>
          <p v-if="deck.description" class="text-sm text-gray-500 line-clamp-2 mb-3">{{ deck.description }}</p>

          <div class="flex items-center justify-between mt-auto">
            <span class="text-sm text-gray-500">{{ deck.terms_count }} söz</span>
            <div class="flex gap-2">
              <button
                @click="router.visit(`/decks/${deck.id}`)"
                class="px-3 py-1.5 text-xs font-medium border border-gray-300 rounded-lg hover:bg-gray-50 transition"
              >Bax</button>
              <button
                @click="cloneDeck(deck.id)"
                class="px-3 py-1.5 text-xs font-medium bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
              >Kopyala</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="decks.last_page > 1" class="flex items-center justify-center gap-2">
        <button
          v-for="link in decks.links"
          :key="link.label"
          @click="link.url && router.visit(link.url)"
          :class="[
            'px-3 py-1.5 text-sm rounded-lg transition',
            link.active ? 'bg-indigo-600 text-white' : 'border border-gray-300 text-gray-600 hover:bg-gray-50',
            !link.url ? 'opacity-50 cursor-default pointer-events-none' : ''
          ]"
          v-html="link.label"
        />
      </div>

    </div>
  </AppLayout>
</template>

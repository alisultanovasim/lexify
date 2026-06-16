<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, watch } from 'vue';
import TermCard from '../../Components/TermCard.vue';
import axios from 'axios';

const props = defineProps({ deck: Object });

// ── Infinite scroll state ────────────────────────────────────────────────
const terms       = ref([]);
const page        = ref(0);
const hasMore     = ref(true);
const loadingMore = ref(false);
const totalCount  = ref(props.deck.terms_count ?? 0);
const termSearch  = ref('');
const sentinel    = ref(null);

let observer    = null;
let searchTimer = null;

const loadMore = async () => {
  if (loadingMore.value || !hasMore.value) return;
  loadingMore.value = true;
  const nextPage = page.value + 1;
  try {
    const res = await axios.get(`/decks/${props.deck.id}/terms`, {
      params: { page: nextPage, search: termSearch.value },
    });
    terms.value   = [...terms.value, ...res.data.terms];
    page.value    = nextPage;
    hasMore.value = res.data.has_more;
    totalCount.value = res.data.total;
  } catch {
    /* silent — user can scroll again to retry */
  } finally {
    loadingMore.value = false;
  }
};

const resetAndLoad = async () => {
  terms.value   = [];
  page.value    = 0;
  hasMore.value = true;
  await loadMore();
};

// Debounced search → reset list and reload from page 1
watch(termSearch, () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(resetAndLoad, 300);
});

// Setup Intersection Observer on sentinel element
const setupObserver = () => {
  observer?.disconnect();
  observer = new IntersectionObserver(
    ([entry]) => { if (entry.isIntersecting) loadMore(); },
    { rootMargin: '300px' },
  );
  if (sentinel.value) observer.observe(sentinel.value);
};

onMounted(async () => {
  await loadMore();
  setupObserver();
});

onUnmounted(() => {
  observer?.disconnect();
  clearTimeout(searchTimer);
});

// ── TermCard event handlers ──────────────────────────────────────────────
const onTermUpdated = (updatedTerm) => {
  const idx = terms.value.findIndex(t => t.id === updatedTerm.id);
  if (idx !== -1) terms.value[idx] = updatedTerm;
};

const onTermDeleted = (termId) => {
  terms.value  = terms.value.filter(t => t.id !== termId);
  totalCount.value = Math.max(0, totalCount.value - 1);
};

// ── Misc ─────────────────────────────────────────────────────────────────
const showDeleteConfirm = ref(false);
const go = (path) => router.visit(path);

const deleteDeck = () => {
  router.delete(`/decks/${props.deck.id}`, {
    onSuccess: () => router.visit('/decks'),
  });
};

const studyModes = [
  { mode: 'flashcard', label: 'Kartlar',  emoji: '🃏' },
  { mode: 'learn',     label: 'Öyrən',    emoji: '🧠' },
  { mode: 'match',     label: 'Uyğunlaş', emoji: '🔗' },
  { mode: 'test',      label: 'Test',     emoji: '📝' },
];
</script>

<template>
  <AppLayout :title="deck.title">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

      <!-- Header -->
      <div class="flex items-start justify-between mb-6">
        <div class="flex items-center gap-3">
          <button @click="go('/decks')" class="text-gray-400 hover:text-gray-600 transition text-lg">←</button>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-2xl font-bold text-gray-900">{{ deck.title }}</h1>
              <span v-if="deck.is_public" class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">İctimai</span>
            </div>
            <p class="text-sm text-gray-500 mt-0.5">
              <span v-if="deck.source_language">{{ deck.source_language.flag_emoji }} {{ deck.source_language.native_name }}</span>
              <span v-if="deck.source_language && deck.target_language"> → </span>
              <span v-if="deck.target_language">{{ deck.target_language.flag_emoji }} {{ deck.target_language.native_name }}</span>
              · {{ totalCount }} söz
            </p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <button @click="go(`/decks/${deck.id}/edit`)" class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition">Düzəliş</button>
          <button @click="go(`/decks/${deck.id}/import`)" class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition">Import</button>
          <button @click="showDeleteConfirm = true" class="px-3 py-1.5 text-sm border border-red-200 text-red-600 rounded-lg hover:bg-red-50 transition">Sil</button>
        </div>
      </div>

      <!-- Study Modes -->
      <div v-if="totalCount > 0" class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-8">
        <button
          v-for="sm in studyModes"
          :key="sm.mode"
          @click="go(`/decks/${deck.id}/study/${sm.mode}`)"
          class="flex flex-col items-center gap-2 p-4 bg-white rounded-2xl border border-gray-200 hover:border-indigo-400 hover:shadow-md transition group cursor-pointer"
        >
          <span class="text-2xl">{{ sm.emoji }}</span>
          <span class="text-sm font-medium text-gray-700 group-hover:text-indigo-600">{{ sm.label }}</span>
        </button>
      </div>

      <!-- Toolbar: search + add -->
      <div class="flex items-center gap-3 mb-4">
        <div class="relative flex-1">
          <input
            v-model="termSearch"
            type="text"
            placeholder="Sözlərdə axtar..."
            class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white"
          />
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔍</span>
          <button v-if="termSearch" @click="termSearch = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <span class="text-sm text-gray-500 whitespace-nowrap">{{ totalCount }} söz</span>
        <button
          @click="go(`/decks/${deck.id}/terms/create`)"
          class="inline-flex items-center gap-1 px-4 py-2 bg-indigo-600 text-white text-sm rounded-xl hover:bg-indigo-700 transition whitespace-nowrap"
        >
          + Söz Əlavə Et
        </button>
      </div>

      <!-- Empty deck -->
      <div v-if="totalCount === 0 && !loadingMore" class="text-center py-16 bg-white rounded-2xl border border-dashed border-gray-300">
        <div class="text-5xl mb-4">📝</div>
        <p class="text-gray-500 mb-4">Bu dəstdə hələ söz yoxdur</p>
        <button @click="go(`/decks/${deck.id}/terms/create`)" class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm hover:bg-indigo-700 transition">
          İlk Sözu Əlavə Et
        </button>
      </div>

      <!-- No search results -->
      <div v-else-if="terms.length === 0 && !loadingMore && termSearch" class="text-center py-12 text-gray-400">
        <div class="text-4xl mb-2">🔍</div>
        <p>«{{ termSearch }}» üçün nəticə tapılmadı</p>
      </div>

      <!-- Terms list -->
      <div v-else class="space-y-3">
        <TermCard
          v-for="term in terms"
          :key="term.id"
          :term="term"
          :deck="deck"
          @updated="onTermUpdated"
          @deleted="onTermDeleted"
        />

        <!-- Sentinel for Intersection Observer -->
        <div ref="sentinel" class="h-1" />

        <!-- Loading skeleton (next batch) -->
        <div v-if="loadingMore" class="space-y-3">
          <div v-for="i in 3" :key="i" class="bg-white rounded-2xl border border-gray-200 p-4 animate-pulse">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-xl bg-gray-200 flex-shrink-0" />
              <div class="flex-1 space-y-2">
                <div class="h-4 bg-gray-200 rounded w-1/3" />
                <div class="h-3 bg-gray-100 rounded w-1/2" />
              </div>
            </div>
          </div>
        </div>

        <!-- End of list -->
        <p v-if="!hasMore && terms.length > 0" class="text-center text-sm text-gray-400 py-4">
          Bütün {{ totalCount }} söz yükləndi
        </p>
      </div>

    </div>

    <!-- Delete confirm modal -->
    <div v-if="showDeleteConfirm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl p-6 max-w-sm w-full">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Dəsti Silin?</h3>
        <p class="text-gray-500 text-sm mb-6">Bu əməliyyat geri qaytarıla bilməz. Bütün sözlər silinəcək.</p>
        <div class="flex gap-3">
          <button @click="showDeleteConfirm = false" class="flex-1 py-2 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50 transition">Ləğv et</button>
          <button @click="deleteDeck" class="flex-1 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700 transition">Sil</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

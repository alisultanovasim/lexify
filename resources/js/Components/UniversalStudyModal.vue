<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  mode: { type: String, required: true },
});
const emit = defineEmits(['close']);

const modeLabels = { learn: 'Öyrən', test: 'Test', match: 'Uyğunlaş' };

const decks    = ref([]);
const selected = ref(new Set());
const search   = ref('');
const loading  = ref(false);
const starting = ref(false);

const filtered = computed(() => {
  const q = search.value.toLowerCase().trim();
  return q ? decks.value.filter(d => d.title.toLowerCase().includes(q)) : decks.value;
});

const isSelected = (id) => selected.value.has(id);

const toggleDeck = (id) => {
  const s = new Set(selected.value);
  s.has(id) ? s.delete(id) : s.add(id);
  selected.value = s;
};

const start = () => {
  if (!selected.value.size || starting.value) return;
  starting.value = true;
  router.post('/universal-study/start', {
    deck_ids: [...selected.value],
    mode: props.mode,
  }, {
    onStart: () => emit('close'),
    onError: () => { starting.value = false; },
  });
};

onMounted(async () => {
  loading.value = true;
  try {
    const res = await axios.get('/universal-study/decks');
    decks.value = res.data;
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <!-- Backdrop -->
  <div
    class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
    @click.self="emit('close')"
  >
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md flex flex-col" style="max-height: 88vh">

      <!-- Header -->
      <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
        <div>
          <h2 class="font-bold text-slate-900 text-base">Ümumi sinaq</h2>
          <p class="text-sm text-slate-600">{{ modeLabels[mode] }} rejimi üçün dəstlər seçin</p>
        </div>
        <button
          @click="emit('close')"
          class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Search -->
      <div class="px-4 pt-4 pb-2">
        <div class="relative">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
          </svg>
          <input
            v-model="search"
            type="text"
            placeholder="Dəst axtar..."
            class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-cyan-400 focus:border-transparent"
          />
        </div>
      </div>

      <!-- Deck list -->
      <div class="flex-1 overflow-y-auto px-4 py-2 space-y-1.5 min-h-0">

        <!-- Loading -->
        <div v-if="loading" class="py-12 flex flex-col items-center gap-2 text-slate-400">
          <div class="w-6 h-6 border-2 border-slate-200 border-t-cyan-500 rounded-full animate-spin"></div>
          <p class="text-sm">Yüklənir...</p>
        </div>

        <!-- Empty state -->
        <div v-else-if="filtered.length === 0" class="py-12 text-center text-slate-400 text-sm">
          Dəst tapılmadı
        </div>

        <!-- Deck rows -->
        <div
          v-for="deck in filtered"
          :key="deck.id"
          @click="toggleDeck(deck.id)"
          class="flex items-center gap-3 px-3 py-3 rounded-xl border-2 cursor-pointer transition select-none"
          :class="isSelected(deck.id)
            ? 'border-cyan-400 bg-cyan-50 shadow-sm'
            : 'border-transparent bg-slate-50 hover:bg-slate-100 hover:border-slate-200'"
        >
          <!-- Color dot -->
          <div
            class="w-3 h-3 rounded-full flex-shrink-0"
            :style="{ backgroundColor: deck.color || '#06B6D4' }"
          ></div>

          <!-- Deck info -->
          <div class="flex-1 min-w-0">
            <p class="font-medium text-slate-900 text-sm truncate">{{ deck.title }}</p>
            <p class="text-xs text-slate-500 mt-0.5">{{ deck.terms_count }} söz</p>
          </div>

          <!-- Checkbox -->
          <div
            class="w-5 h-5 rounded border-2 flex-shrink-0 flex items-center justify-center transition"
            :class="isSelected(deck.id)
              ? 'bg-cyan-500 border-cyan-500'
              : 'border-slate-300 bg-white'"
          >
            <svg v-if="isSelected(deck.id)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-4 py-4 border-t border-slate-100">
        <div class="flex items-center justify-between mb-3">
          <span class="text-sm text-slate-600">
            <template v-if="selected.size > 0">
              <span class="font-semibold text-cyan-600">{{ selected.size }}</span> dəst seçildi
            </template>
            <template v-else>
              Dəst seçin
            </template>
          </span>
        </div>
        <button
          @click="start"
          :disabled="!selected.size || starting"
          class="w-full py-3 font-semibold rounded-xl transition"
          :class="selected.size
            ? 'bg-cyan-500 hover:bg-cyan-600 text-white'
            : 'bg-slate-100 text-slate-400 cursor-not-allowed'"
        >
          {{ starting ? 'Başlanır...' : 'Başla →' }}
        </button>
      </div>

    </div>
  </div>
</template>

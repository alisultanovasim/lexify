<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  terms:         Array,
  session:       Object,
  deck:          Object,
  matchProgress: { type: Object, default: null },
});

const MAX_PAIRS = 8;
const shuffle   = (arr) => [...arr].sort(() => Math.random() - 0.5);

const pairs      = ref([]);
const leftItems  = ref([]);
const rightItems = ref([]);
const selected   = ref(null);
const matched    = ref(new Set());
const wrong      = ref(new Set());
const completed  = ref(false);
const score      = ref({ correct: 0, total: 0 });

onMounted(() => {
  const subset         = shuffle(props.terms).slice(0, MAX_PAIRS);
  pairs.value          = subset;
  score.value.total    = subset.length;
  leftItems.value      = shuffle(subset.map(t => ({ id: t.id, text: t.term,       side: 'left'  })));
  rightItems.value     = shuffle(subset.map(t => ({ id: t.id, text: t.definition, side: 'right' })));
});

const allMatchedIds = computed(() => new Set([...matched.value].map(k => k.split('-')[0])));
const isAllMatched  = computed(() => allMatchedIds.value.size === pairs.value.length);

const getItemKey = (item) => `${item.id}-${item.side}`;

const select = async (item) => {
  const key = getItemKey(item);
  if (matched.value.has(key) || wrong.value.has(key)) return;

  if (!selected.value) { selected.value = item; return; }
  if (selected.value.side === item.side) { selected.value = item; return; }

  const a = selected.value;
  const b = item;

  if (a.id === b.id) {
    matched.value = new Set([...matched.value, getItemKey(a), getItemKey(b)]);
    score.value.correct++;
    selected.value = null;

    await axios.post(`/study/${props.session.id}/answer`, {
      term_id: a.id, is_correct: true, rating: 5,
    }).catch(() => {});

    if (isAllMatched.value) {
      await axios.post(`/study/${props.session.id}/complete`);
      setTimeout(() => { completed.value = true; }, 600);
    }
  } else {
    wrong.value = new Set([...wrong.value, getItemKey(a), getItemKey(b)]);
    setTimeout(() => {
      wrong.value.delete(getItemKey(a));
      wrong.value.delete(getItemKey(b));
      wrong.value    = new Set(wrong.value);
      selected.value = null;
    }, 800);

    await axios.post(`/study/${props.session.id}/answer`, {
      term_id: a.id, is_correct: false, rating: 1,
    }).catch(() => {});
  }
};

const getItemClass = (item) => {
  const key = getItemKey(item);
  if (matched.value.has(key))                                          return 'bg-green-100 border-green-400 text-green-800 cursor-default';
  if (wrong.value.has(key))                                            return 'bg-red-100 border-red-400 text-red-800 animate-shake';
  if (selected.value && getItemKey(selected.value) === key)            return 'bg-indigo-100 border-indigo-500 text-indigo-800 scale-105';
  return 'bg-white border-gray-200 text-gray-800 hover:border-indigo-400 hover:bg-indigo-50 cursor-pointer';
};

// Overall progress (across all sessions for this deck)
const overallProgress = computed(() => {
  if (!props.matchProgress) return null;
  const { matched: m, total, reset } = props.matchProgress;
  // After current batch is done, add current score
  const newlyMatched = completed.value ? m + score.value.correct : m;
  return { current: newlyMatched, total, reset };
});
</script>

<template>
  <!-- ── Completed ─────────────────────────────────────────────────────── -->
  <div v-if="completed" class="text-center py-16">
    <div class="text-6xl mb-4">🎯</div>
    <h2 class="text-2xl font-bold text-gray-900 mb-2">Uyğunlaşdırıldı!</h2>
    <p class="text-gray-500 mb-2">{{ score.correct }} / {{ score.total }} cüt tapıldı</p>

    <!-- Overall match progress -->
    <div v-if="matchProgress" class="mx-auto max-w-xs mb-8">
      <div class="flex justify-between text-sm text-gray-500 mb-1">
        <span>Ümumi irəliləyiş</span>
        <span>{{ Math.min(matchProgress.matched + score.correct, matchProgress.total) }} / {{ matchProgress.total }}</span>
      </div>
      <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
        <div class="h-full bg-indigo-500 rounded-full transition-all"
          :style="{ width: matchProgress.total > 0
            ? (Math.min(matchProgress.matched + score.correct, matchProgress.total) / matchProgress.total * 100) + '%'
            : '0%' }" />
      </div>
      <p v-if="matchProgress.matched + score.correct >= matchProgress.total" class="text-xs text-green-600 mt-1 font-medium">
        🎉 Bütün sözlər uyğunlaşdırıldı!
      </p>
    </div>

    <div class="flex gap-3 justify-center">
      <button @click="router.visit(`/decks/${deck.id}/study/match`)"
        class="px-6 py-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition">
        {{ matchProgress && (matchProgress.matched + score.correct) >= matchProgress.total ? '🔄 Yenidən' : 'Davam et →' }}
      </button>
      <button @click="router.visit(`/decks/${deck.id}`)"
        class="px-6 py-3 border border-gray-300 rounded-xl hover:bg-gray-50 transition">Dəstə Qayıt</button>
    </div>
  </div>

  <!-- ── Study ─────────────────────────────────────────────────────────── -->
  <div v-else>

    <!-- Reset notice -->
    <div v-if="matchProgress?.reset"
      class="mb-4 p-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700 text-center">
      🎉 Bütün sözlər uyğunlaşdırılmışdı — yenidən başlayırsınız!
    </div>

    <!-- Overall progress bar -->
    <div v-if="matchProgress && !matchProgress.reset" class="mb-4">
      <div class="flex justify-between text-xs text-gray-400 mb-1">
        <span>Ümumi irəliləyiş</span>
        <span>{{ matchProgress.matched }} / {{ matchProgress.total }} uyğunlaşdırılıb</span>
      </div>
      <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
        <div class="h-full bg-indigo-400 rounded-full transition-all"
          :style="{ width: matchProgress.total > 0 ? (matchProgress.matched / matchProgress.total * 100) + '%' : '0%' }" />
      </div>
    </div>

    <p class="text-center text-gray-500 text-sm mb-6">Sol tərəfdən söz, sağ tərəfdən tərcüməni seçin</p>

    <div class="grid grid-cols-2 gap-3">
      <div class="space-y-2">
        <button v-for="item in leftItems" :key="item.id"
          @click="select(item)"
          class="w-full p-3 rounded-xl border-2 text-sm font-medium text-left transition"
          :class="getItemClass(item)">
          {{ item.text }}
        </button>
      </div>
      <div class="space-y-2">
        <button v-for="item in rightItems" :key="item.id"
          @click="select(item)"
          class="w-full p-3 rounded-xl border-2 text-sm font-medium text-left transition"
          :class="getItemClass(item)">
          {{ item.text }}
        </button>
      </div>
    </div>
  </div>
</template>

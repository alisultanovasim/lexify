<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps({ deck: Object });

// ── Parse state ──────────────────────────────────────────────────────────
const rawText    = ref('');
const mode       = ref('semicolon');
const lineSep    = ref('tab');
const preview    = ref([]);
const parseError = ref('');

const parseSemicolon = (text) => {
  const entries = text.replace(/\n/g, ';').split(';').map(e => e.trim()).filter(e => e.length > 0);
  const rows = [];
  for (const entry of entries) {
    const firstComma = entry.indexOf(',');
    if (firstComma === -1) {
      parseError.value = `Xəta: "${entry}" — vergül (,) tapılmadı`;
      return [];
    }
    const term       = entry.slice(0, firstComma).trim();
    const definition = entry.slice(firstComma + 1).trim();
    if (!term || !definition) continue;
    rows.push({ term, definition, notes: '' });
  }
  return rows;
};

const parseLines = (text) => {
  const sep  = lineSep.value === 'tab' ? '\t' : lineSep.value === 'comma' ? ',' : ';';
  const rows = [];
  for (const line of text.split('\n').filter(l => l.trim())) {
    const parts = line.split(sep);
    if (parts.length < 2) {
      parseError.value = `Xəta: "${line}" — ən az 2 sütun lazımdır`;
      return [];
    }
    rows.push({ term: parts[0]?.trim() || '', definition: parts[1]?.trim() || '', notes: parts[2]?.trim() || '' });
  }
  return rows;
};

const parse = () => {
  parseError.value = '';
  preview.value    = [];
  const rows = mode.value === 'semicolon' ? parseSemicolon(rawText.value) : parseLines(rawText.value);
  if (!parseError.value) preview.value = rows;
};

const entryCount = computed(() => {
  if (mode.value === 'semicolon') return rawText.value.split(';').filter(e => e.trim()).length;
  return rawText.value.split('\n').filter(l => l.trim()).length;
});

// ── Import state machine ─────────────────────────────────────────────────
// 'idle' | 'creating' | 'enriching' | 'done' | 'error'
const importState    = ref('idle');
const importError    = ref('');
const createdTerms   = ref([]);      // [{id, term}] returned from backend
const enrichedCount  = ref(0);       // how many enrichments finished
const failedCount    = ref(0);
const enrichStart    = ref(0);       // timestamp when enrichment loop started
const currentTerm    = ref('');      // word currently being enriched
const enrichedSet    = ref(new Set()); // IDs that finished enrichment (for preview badges)

const totalTerms    = computed(() => createdTerms.value.length);
const progressPct   = computed(() =>
  totalTerms.value ? Math.round((enrichedCount.value / totalTerms.value) * 100) : 0,
);

const estimatedRemaining = computed(() => {
  if (importState.value !== 'enriching' || enrichedCount.value === 0) return null;
  const elapsed   = Date.now() - enrichStart.value;
  const avgMs     = elapsed / enrichedCount.value;
  const remaining = (totalTerms.value - enrichedCount.value) * avgMs;
  const secs      = Math.ceil(remaining / 1000);
  if (secs < 60) return `~${secs} san. qalır`;
  return `~${Math.ceil(secs / 60)} dəq. qalır`;
});

// Map preview index → term status for badges
const termStatus = computed(() => {
  const map = {};
  createdTerms.value.forEach((t, i) => {
    if (enrichedSet.value.has(t.id))      map[i] = 'done';
    else if (currentTerm.value === t.term) map[i] = 'active';
    else if (importState.value === 'enriching' && i >= enrichedCount.value) map[i] = 'waiting';
  });
  return map;
});

// ── Submit ───────────────────────────────────────────────────────────────
const submit = async () => {
  if (!preview.value.length) return;

  importState.value   = 'creating';
  importError.value   = '';
  createdTerms.value  = [];
  enrichedCount.value = 0;
  failedCount.value   = 0;
  enrichedSet.value   = new Set();

  try {
    // Step 1: create all terms (instant DB inserts)
    const res = await axios.post(`/decks/${props.deck.id}/import`, { rows: preview.value });

    createdTerms.value = res.data.terms;
    importState.value  = 'enriching';
    enrichStart.value  = Date.now();

    // Step 2: enrich each term one by one, tracking progress
    for (const term of res.data.terms) {
      currentTerm.value = term.term;
      try {
        await axios.post(`/terms/${term.id}/enrich`);
        enrichedSet.value = new Set([...enrichedSet.value, term.id]);
      } catch {
        failedCount.value++;
      }
      enrichedCount.value++;
    }

    currentTerm.value = '';
    importState.value = 'done';

    setTimeout(() => router.visit(`/decks/${props.deck.id}`), 2500);

  } catch (e) {
    importState.value = 'error';
    importError.value = e.response?.status === 422
      ? 'Məlumatları yoxlayın: ' + (Object.values(e.response.data.errors || {})[0]?.[0] ?? 'bilinməyən xəta')
      : 'Xəta baş verdi. Yenidən cəhd edin.';
  }
};
</script>

<template>
  <AppLayout title="Import">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

      <div class="flex items-center gap-3 mb-8">
        <button @click="router.visit(`/decks/${props.deck.id}`)" class="text-gray-400 hover:text-gray-600">←</button>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Toplu Import</h1>
          <p class="text-sm text-gray-500">{{ deck.title }}</p>
        </div>
      </div>

      <!-- ── Progress overlay (enriching / done / error) ─────────────────── -->
      <div v-if="importState !== 'idle' && importState !== 'creating'"
        class="bg-white rounded-2xl border border-gray-200 p-6 mb-6">

        <!-- Done -->
        <div v-if="importState === 'done'" class="text-center py-4">
          <div class="text-5xl mb-3">✅</div>
          <p class="text-lg font-semibold text-gray-900">Import tamamlandı!</p>
          <p class="text-sm text-gray-500 mt-1">
            {{ totalTerms }} söz yaradıldı,
            {{ totalTerms - failedCount }} söz AI ilə zənginləşdirildi
            <span v-if="failedCount">, {{ failedCount }} uğursuz</span>
          </p>
          <p class="text-xs text-gray-400 mt-2">Dəstə yönləndirilirsiniz...</p>
        </div>

        <!-- Error -->
        <div v-else-if="importState === 'error'" class="text-center py-4">
          <div class="text-4xl mb-3">⚠️</div>
          <p class="text-red-600 font-medium">{{ importError }}</p>
          <button @click="importState = 'idle'" class="mt-4 px-4 py-2 text-sm border border-gray-300 rounded-xl hover:bg-gray-50">
            Geri qayıt
          </button>
        </div>

        <!-- Enriching -->
        <div v-else>
          <div class="flex items-center justify-between mb-3">
            <p class="font-semibold text-gray-900">AI zənginləşdirmə</p>
            <span class="text-sm font-mono text-indigo-600">
              {{ enrichedCount }}&nbsp;/&nbsp;{{ totalTerms }}&nbsp;({{ progressPct }}%)
            </span>
          </div>

          <!-- Progress bar -->
          <div class="w-full bg-gray-100 rounded-full h-4 mb-3 overflow-hidden">
            <div
              class="h-4 rounded-full bg-gradient-to-r from-indigo-500 to-indigo-600 transition-all duration-500 ease-out"
              :style="{ width: progressPct + '%' }"
            />
          </div>

          <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
            <span class="flex items-center gap-1.5 min-w-0">
              <svg class="animate-spin h-3.5 w-3.5 text-indigo-500 flex-shrink-0" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
              </svg>
              <span class="truncate italic">{{ currentTerm || '...' }}</span>
            </span>
            <span v-if="estimatedRemaining" class="text-xs text-gray-400 ml-3 flex-shrink-0">
              {{ estimatedRemaining }}
            </span>
          </div>

          <!-- Live term list with status badges -->
          <div class="border border-gray-100 rounded-xl overflow-hidden">
            <div class="max-h-56 overflow-y-auto divide-y divide-gray-50">
              <div
                v-for="(t, i) in createdTerms"
                :key="t.id"
                class="flex items-center gap-3 px-4 py-2 text-sm"
                :class="termStatus[i] === 'active' ? 'bg-indigo-50' : ''"
              >
                <!-- Status icon -->
                <span class="w-5 flex-shrink-0 text-center">
                  <template v-if="termStatus[i] === 'done'">
                    <span class="text-green-500 text-base">✓</span>
                  </template>
                  <template v-else-if="termStatus[i] === 'active'">
                    <svg class="animate-spin h-4 w-4 text-indigo-500 inline" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                  </template>
                  <template v-else>
                    <span class="text-gray-300 text-xs">○</span>
                  </template>
                </span>
                <span class="text-xs text-gray-400 w-6 flex-shrink-0">{{ i + 1 }}</span>
                <span
                  :class="['font-medium truncate', termStatus[i] === 'done' ? 'text-gray-700' : termStatus[i] === 'active' ? 'text-indigo-700' : 'text-gray-400']"
                >{{ t.term }}</span>
                <span v-if="termStatus[i] === 'done'" class="ml-auto text-xs text-green-500 flex-shrink-0">AI ✨</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Main input/preview panels (hidden while processing) ──────────── -->
      <div v-if="importState === 'idle' || importState === 'creating'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Input panel -->
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
          <h2 class="font-semibold text-gray-900 mb-4">Məlumat Daxil Edin</h2>

          <div class="flex gap-2 mb-4">
            <button @click="mode = 'semicolon'; preview = []"
              :class="['flex-1 py-2 text-sm rounded-xl border transition font-medium',
                mode === 'semicolon' ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-300 text-gray-600 hover:bg-gray-50']">
              Nöqtəli vergül (;)
            </button>
            <button @click="mode = 'lines'; preview = []"
              :class="['flex-1 py-2 text-sm rounded-xl border transition font-medium',
                mode === 'lines' ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-300 text-gray-600 hover:bg-gray-50']">
              Sətir-sətir
            </button>
          </div>

          <div v-if="mode === 'semicolon'" class="bg-blue-50 border border-blue-200 rounded-xl p-3 mb-4 text-sm text-blue-700">
            <p class="font-medium mb-1">Format:</p>
            <code class="text-xs break-all">söz,tərcümə;söz,tərcümə;...</code>
            <p class="mt-1 text-xs">Nümunə:</p>
            <code class="text-xs break-all">die Bedeutung,əhəmiyyət;verbessern,yaxşılaşdırmaq</code>
          </div>

          <div v-else class="mb-4">
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 mb-3 text-sm text-blue-700">
              <code class="text-xs">söz [ayırıcı] tərcümə</code>
              <span class="ml-2 text-xs">— hər sətir bir söz</span>
            </div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Sahə ayırıcısı</label>
            <div class="flex gap-2">
              <button v-for="s in [{v:'tab',l:'Tab'},{v:'comma',l:','},{v:';',l:';'}]" :key="s.v"
                @click="lineSep = s.v"
                :class="['px-3 py-1.5 text-sm rounded-lg border transition',
                  lineSep === s.v ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-300 text-gray-600 hover:bg-gray-50']">
                {{ s.l }}
              </button>
            </div>
          </div>

          <textarea
            v-model="rawText"
            rows="10"
            :placeholder="mode === 'semicolon'
              ? 'die Bedeutung,əhəmiyyət;verbessern,yaxşılaşdırmaq;die Lebensqualität,həyat keyfiyyəti'
              : 'Haus\tev\nBaum\tağac\nBlume\tçiçək'"
            class="w-full px-3 py-2 border border-gray-300 rounded-xl font-mono text-sm focus:ring-2 focus:ring-indigo-500 outline-none resize-none"
          ></textarea>

          <p v-if="parseError" class="text-red-500 text-xs mt-2">{{ parseError }}</p>

          <button @click="parse" :disabled="!rawText.trim()"
            class="mt-3 w-full py-2.5 border-2 border-indigo-600 text-indigo-600 rounded-xl font-medium hover:bg-indigo-50 disabled:opacity-50 transition">
            Önizlə ({{ entryCount }} entry)
          </button>
        </div>

        <!-- Preview panel -->
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-900">Önizlə</h2>
            <span v-if="preview.length" class="text-sm text-gray-500">{{ preview.length }} söz</span>
          </div>

          <div v-if="!preview.length" class="text-center py-12 text-gray-400">
            <div class="text-4xl mb-2">👁</div>
            <p class="text-sm">Sol tərəfə məlumat daxil edib "Önizlə" düyməsini basın</p>
          </div>

          <div v-else>
            <div class="overflow-y-auto max-h-64 space-y-1 mb-4">
              <div v-for="(row, i) in preview" :key="i"
                class="flex items-start gap-3 p-2 rounded-lg hover:bg-gray-50 text-sm">
                <span class="text-gray-400 text-xs w-6 flex-shrink-0 pt-0.5">{{ i + 1 }}</span>
                <span class="font-medium text-gray-900 w-2/5 truncate">{{ row.term }}</span>
                <span class="text-gray-600 flex-1 truncate">{{ row.definition }}</span>
              </div>
            </div>

            <!-- Submit button / creating state -->
            <div v-if="importState === 'creating'" class="w-full py-3 bg-indigo-600 text-white rounded-xl flex items-center justify-center gap-2 opacity-80">
              <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
              </svg>
              <span>Sözlər yaradılır...</span>
            </div>
            <button v-else @click="submit"
              class="w-full py-3 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 transition flex items-center justify-center gap-2">
              ✨ {{ preview.length }} Sözu Import Et + AI
            </button>

            <p class="text-xs text-center text-gray-400 mt-2">
              Import sonrası AI tələffüz, cins, nümunə cümlə dolduracaq
            </p>
          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

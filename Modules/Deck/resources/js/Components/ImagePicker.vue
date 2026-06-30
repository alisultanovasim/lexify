<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  termId: Number,
  initialQuery: String,
  lang: { type: String, default: 'en' },
});

const emit = defineEmits(['close', 'selected']);

const query      = ref(props.initialQuery || '');
const images     = ref([]);
const loading    = ref(false);
const savingIdx  = ref(-1);   // index of image being saved (-1 = none)
const saveError  = ref('');
const setupError = ref('');
const searchError = ref('');

const errorMessages = {
  no_api_key: 'Pixabay API key tapılmadı',
  quota:      'Pixabay limiti aşıldı',
  exception:  'Şəbəkə xətası',
  error:      'Pixabay API xətası',
};

const setupInstructions = {
  no_api_key: 'pixabay.com/api/docs → qeydiyyat → API key kopyala → .env-ə PIXABAY_API_KEY= yaz → php artisan optimize',
};

// Sequence counter: prevents a slow/stale search from overwriting a newer one
let searchSeq = 0;

const search = async () => {
  if (!query.value.trim()) return;
  const seq = ++searchSeq;

  loading.value = true;
  searchError.value = '';
  setupError.value = '';
  saveError.value = '';
  images.value = [];

  try {
    const res = await axios.get('/image-search', { params: { q: query.value, lang: props.lang } });
    // Discard if a newer search has already started
    if (seq !== searchSeq) return;
    images.value = res.data.images || [];
    if (res.data.error) setupError.value = res.data.error;
  } catch {
    if (seq !== searchSeq) return;
    searchError.value = 'Sorğu uğursuz oldu';
  } finally {
    if (seq === searchSeq) loading.value = false;
  }
};

const selectImage = async (img, index) => {
  // Prevent double-click while already saving
  if (savingIdx.value !== -1) return;

  savingIdx.value = index;
  saveError.value = '';

  try {
    const res = await axios.post(`/terms/${props.termId}/image`, {
      url:      img.url,
      alt_text: (img.title || '').substring(0, 200),
    });
    emit('selected', res.data.image.url);
  } catch (e) {
    console.error('[ImagePicker] save error:', e?.response?.status, e?.response?.data, e?.message)
    if (e?.response?.status === 419) {
      saveError.value = 'Sessiya vaxtı bitib. Zəhmət olmasa səhifəni yeniləyin (F5).';
    } else {
      const status = e?.response?.status ? ` (${e.response.status})` : ''
      saveError.value = e?.response?.data?.message
        || e?.response?.data?.errors?.url?.[0]
        || `Xəta${status}: saxlama uğursuz oldu`
    }
    savingIdx.value = -1
  }
  // Note: on success, parent closes the picker via @selected handler
  // If parent doesn't close, reset after 5s
  setTimeout(() => { savingIdx.value = -1; }, 5000);
};

onMounted(() => {
  if (props.initialQuery) search();
});
</script>

<template>
  <div class="fixed inset-0 bg-black/60 flex items-center justify-center z-[60] p-4">
    <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] flex flex-col">

      <!-- Header -->
      <div class="flex items-center justify-between p-5 border-b border-gray-100">
        <h3 class="text-lg font-semibold text-gray-900">Şəkil Seçin</h3>
        <button @click="emit('close')" class="text-gray-400 hover:text-gray-600 text-xl">×</button>
      </div>

      <!-- Search bar -->
      <div class="p-4 border-b border-gray-100">
        <div class="flex gap-2">
          <input
            v-model="query"
            type="text"
            placeholder="Axtarış et..."
            class="flex-1 px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cyan-500 outline-none"
            @keyup.enter="search"
          />
          <button
            @click="search"
            :disabled="loading || !query.trim()"
            class="px-5 py-2 bg-cyan-600 text-white rounded-xl hover:bg-cyan-700 disabled:opacity-50 transition font-medium"
          >
            {{ loading ? '...' : 'Axtar' }}
          </button>
        </div>
      </div>

      <!-- Content -->
      <div class="flex-1 overflow-y-auto p-4">

        <!-- Setup error -->
        <div v-if="setupError" class="py-6 text-center">
          <div class="text-3xl mb-2">⚠️</div>
          <p class="font-semibold text-gray-800 mb-1">{{ errorMessages[setupError] || setupError }}</p>
          <div v-if="setupInstructions[setupError]"
            class="mt-3 text-left bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-800 max-w-sm mx-auto">
            <p class="font-semibold mb-1">Həll:</p>
            <p>{{ setupInstructions[setupError] }}</p>
          </div>
        </div>

        <!-- Loading skeletons -->
        <div v-else-if="loading" class="grid grid-cols-3 gap-3">
          <div v-for="i in 9" :key="i" class="aspect-square bg-gray-100 rounded-xl animate-pulse"></div>
        </div>

        <!-- Search error -->
        <div v-else-if="searchError" class="text-center py-8 text-red-500 text-sm">{{ searchError }}</div>

        <!-- Images grid — only the clicked image shows spinner -->
        <div v-else-if="images.length" class="grid grid-cols-3 gap-3">
          <button
            v-for="(img, i) in images"
            :key="i"
            @click="selectImage(img, i)"
            :disabled="savingIdx !== -1"
            class="aspect-square rounded-xl overflow-hidden border-2 transition group relative"
            :class="savingIdx === i
              ? 'border-cyan-500 cursor-wait'
              : savingIdx !== -1
                ? 'border-transparent opacity-70 cursor-not-allowed'
                : 'border-transparent hover:border-cyan-500 cursor-pointer'"
          >
            <img
              :src="img.thumbnail"
              :alt="img.title"
              class="w-full h-full object-cover transition duration-200"
              :class="savingIdx === -1 ? 'group-hover:scale-105' : ''"
            />

            <!-- Spinner only on the image being saved -->
            <div v-if="savingIdx === i"
              class="absolute inset-0 bg-black/40 flex items-center justify-center">
              <svg class="animate-spin h-7 w-7 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
              </svg>
            </div>

            <!-- Hover checkmark (only when nothing is saving) -->
            <div v-else-if="savingIdx === -1"
              class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition flex items-center justify-center">
              <span class="text-white text-2xl opacity-0 group-hover:opacity-100 transition">✓</span>
            </div>
          </button>
        </div>

        <!-- Empty state -->
        <div v-else-if="!loading && query" class="text-center py-10 text-gray-400">
          <div class="text-4xl mb-2">🔍</div>
          <p>Nəticə tapılmadı</p>
        </div>

        <div v-else class="text-center py-10 text-gray-400">
          <div class="text-4xl mb-2">🖼️</div>
          <p>Söz daxil edib axtar</p>
        </div>

      </div>

      <!-- Save error message -->
      <div v-if="saveError" class="px-4 pb-3 text-center text-sm text-red-500">{{ saveError }}</div>

    </div>
  </div>
</template>

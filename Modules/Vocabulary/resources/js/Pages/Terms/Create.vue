<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({ deck: Object });

const form = useForm({ term: '', definition: '' });

const enriching    = ref(false);
const enrichMsg    = ref('');
const enrichOk     = ref(false);
const enrichedTerm = ref(null); // stores enriched term data for display

// Image suggestion after word add
const showImages      = ref(false);
const lastTermId      = ref(null);
const imgLoading      = ref(false);
const savingIndex     = ref(-1);   // index of image being saved (-1 = none)
const suggestedImages = ref([]);
const savedImageUrl   = ref(null); // thumbnail of saved image for preview
const imgError        = ref('');
const imgSearchQuery  = ref('');   // manual retry search

const submit = () => {
  const savedTerm = form.term;
  const savedDef  = form.definition;

  form.post(`/decks/${props.deck.id}/terms`, {
    onSuccess: () => {
      // 1. Reset form immediately
      form.reset();
      enrichMsg.value  = '';
      enrichOk.value   = false;
      enrichedTerm.value = null;

      // 2. Start AI enrichment + image search in parallel
      runEnrich(savedTerm, savedDef);
      openImageSuggest(savedTerm);
    },
  });
};

const runEnrich = async (term, definition) => {
  enriching.value = true;
  try {
    const res = await axios.post(`/decks/${props.deck.id}/terms/enrich-last`, { term, definition });
    const msgs = {
      ok:      '✓ AI tamamladı (tələffüz, cəm, nümunə)',
      quota:   '⏳ AI limit aşıldı',
      no_key:  '⚠ API key tapılmadı',
      expired: '⚠ API key vaxtı bitib',
      error:   '✕ AI xətası',
    };
    enrichMsg.value = msgs[res.data.status] || '✕ Naməlum xəta';
    enrichOk.value  = res.data.success;
    if (res.data.term?.id) {
      lastTermId.value = res.data.term.id;
      if (res.data.success) enrichedTerm.value = res.data.term;
    }
  } catch {
    enrichMsg.value = '✕ Server xətası';
  } finally {
    enriching.value = false;
    setTimeout(() => { enrichMsg.value = ''; }, 5000);
  }
};

const openImageSuggest = async (termText) => {
  imgError.value = '';
  suggestedImages.value = [];
  imgSearchQuery.value = termText;

  // Step 1: Get term ID BEFORE showing images
  try {
    const t = await axios.get(`/decks/${props.deck.id}/terms/last`);
    if (t.data.term?.id) {
      lastTermId.value = t.data.term.id;
    } else {
      return;
    }
  } catch {
    return;
  }

  // Step 2: Show panel and load images
  showImages.value = true;
  await searchImages(termText);
};

const searchImages = async (query) => {
  imgLoading.value = true;
  imgError.value = '';
  suggestedImages.value = [];
  try {
    const res = await axios.get('/image-search', {
      params: { q: query, lang: props.deck.source_language?.code || 'en' },
    });
    suggestedImages.value = res.data.images || [];
    if (res.data.error) imgError.value = res.data.error;
  } catch {
    imgError.value = 'search_failed';
  } finally {
    imgLoading.value = false;
  }
};

const retrySearch = () => {
  if (imgSearchQuery.value.trim()) {
    searchImages(imgSearchQuery.value.trim());
  }
};

const selectImage = async (img, index) => {
  if (!lastTermId.value || savingIndex.value !== -1) return;
  savingIndex.value = index;
  try {
    await axios.post(`/terms/${lastTermId.value}/image`, {
      url: img.url,
      alt_text: img.title || '',
    });
    // Show saved thumbnail as confirmation before closing
    savedImageUrl.value = img.thumbnail;
    closeImages();
  } catch {
    imgError.value = 'save_failed';
  } finally {
    savingIndex.value = -1;
  }
};

const closeImages = () => {
  showImages.value = false;
  suggestedImages.value = [];
  lastTermId.value = null;
  imgError.value = '';
  savingIndex.value = -1;
  imgSearchQuery.value = '';
  // Clear saved preview after 4 seconds
  if (savedImageUrl.value) {
    setTimeout(() => { savedImageUrl.value = null; }, 4000);
  }
};

const imgErrorMessages = {
  no_api_key:  'Pixabay API key tapılmadı',
  quota:       'Pixabay gündəlik limit dolub',
  search_failed:'Şəbəkə xətası',
  error:       'Pixabay API xətası',
  save_failed: 'Şəkil saxlanarkən xəta baş verdi.',
};
</script>

<template>
  <AppLayout title="Söz Əlavə Et">
    <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

      <div class="flex items-center gap-3 mb-8">
        <button @click="router.visit(`/decks/${props.deck.id}`)" class="text-gray-400 hover:text-gray-600">←</button>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Söz Əlavə Et</h1>
          <p class="text-sm text-gray-500">{{ deck.title }}</p>
        </div>
      </div>

      <!-- AI info banner -->
      <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-4">
        <div class="flex items-start gap-3 p-3 bg-cyan-50 border border-cyan-100 rounded-xl mb-5 text-sm text-cyan-700">
          <span class="text-lg">✨</span>
          <p>Söz + tərcümə yazın — AI <strong>tələffüz, cəm forması, nümunə cümlə</strong> avtomatik dolduracaq. Şəkil seçimi də göstəriləcək.</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label for="term" class="block text-sm font-medium text-gray-700 mb-1">
              Söz <span v-if="deck.source_language" class="text-cyan-500">{{ deck.source_language.flag_emoji }}</span>
            </label>
            <input id="term" v-model="form.term" name="term" type="text"
              placeholder="məs: das Haus"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cyan-500 outline-none text-lg"
              required autofocus />
            <p v-if="form.errors.term" class="text-red-500 text-xs mt-1">{{ form.errors.term }}</p>
          </div>

          <div>
            <label for="definition" class="block text-sm font-medium text-gray-700 mb-1">
              Tərcümə <span v-if="deck.target_language" class="text-cyan-500">{{ deck.target_language.flag_emoji }}</span>
            </label>
            <input id="definition" v-model="form.definition" name="definition" type="text"
              placeholder="məs: ev"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cyan-500 outline-none text-lg"
              required />
            <p v-if="form.errors.definition" class="text-red-500 text-xs mt-1">{{ form.errors.definition }}</p>
          </div>

          <button type="submit" :disabled="form.processing || enriching"
            class="w-full py-3 bg-cyan-600 text-white rounded-xl font-medium hover:bg-cyan-700 disabled:opacity-60 transition flex items-center justify-center gap-2">
            <svg v-if="form.processing || enriching" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
            <span v-if="form.processing">Saxlanır...</span>
            <span v-else-if="enriching">✨ AI işləyir...</span>
            <span v-else>✨ Əlavə et</span>
          </button>

          <!-- AI status message -->
          <p v-if="enrichMsg && !enrichedTerm" class="text-center text-sm"
            :class="enrichOk ? 'text-green-600' : 'text-orange-500'">
            {{ enrichMsg }}
          </p>

          <!-- AI enrichment result card -->
          <Transition enter-active-class="transition duration-300" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100">
            <div v-if="enrichedTerm" class="mt-3 p-3 bg-green-50 border border-green-200 rounded-xl text-sm">
              <p class="text-green-700 font-medium mb-2 flex items-center gap-1">
                <span>✨</span> AI tamamladı
              </p>
              <div class="flex flex-wrap gap-2 items-center">
                <!-- Gender badge -->
                <span v-if="enrichedTerm.gender" class="font-bold px-2 py-0.5 rounded text-sm"
                  :class="{
                    'bg-blue-100 text-blue-700':   enrichedTerm.gender === 'der',
                    'bg-pink-100 text-pink-700':   enrichedTerm.gender === 'die',
                    'bg-yellow-100 text-yellow-700': enrichedTerm.gender === 'das',
                  }">{{ enrichedTerm.gender }}</span>

                <!-- Term + plural -->
                <span class="font-semibold text-gray-900">{{ enrichedTerm.term }}</span>
                <span v-if="enrichedTerm.plural_form" class="text-gray-500">/ {{ enrichedTerm.plural_form }}</span>

                <!-- Pronunciation -->
                <span v-if="enrichedTerm.pronunciation" class="font-mono text-cyan-600 text-xs">/{{ enrichedTerm.pronunciation }}/</span>

                <!-- Part of speech -->
                <span v-if="enrichedTerm.part_of_speech" class="bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded text-xs">
                  {{ { noun:'İsim', verb:'Fel', adjective:'Sifət', adverb:'Zərf', phrase:'İfadə', other:'Digər' }[enrichedTerm.part_of_speech] }}
                </span>
              </div>

              <!-- Example sentence -->
              <div v-if="enrichedTerm.examples?.length" class="mt-2 pt-2 border-t border-green-100">
                <p class="text-gray-700 italic text-xs">{{ enrichedTerm.examples[0].sentence }}</p>
                <p v-if="enrichedTerm.examples[0].translation" class="text-gray-500 text-xs">{{ enrichedTerm.examples[0].translation }}</p>
              </div>
            </div>
          </Transition>
        </form>
      </div>

      <!-- Image save confirmation -->
      <Transition enter-active-class="transition duration-300" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-300" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="savedImageUrl" class="flex items-center gap-3 bg-green-50 border border-green-200 rounded-2xl p-3 mb-4">
          <img :src="savedImageUrl" class="w-14 h-14 rounded-xl object-cover flex-shrink-0" />
          <div>
            <p class="text-sm font-medium text-green-700">✓ Şəkil əlavə edildi</p>
            <p class="text-xs text-green-500">Dəstə qayıdanda görünəcək</p>
          </div>
        </div>
      </Transition>

      <!-- Image suggestion panel -->
      <Transition enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 translate-y-2"
        enter-to-class="opacity-100 translate-y-0">
        <div v-if="showImages" class="bg-white rounded-2xl border border-gray-200 p-5">

          <!-- Header with manual search -->
          <div class="flex items-center gap-2 mb-4">
            <span class="font-semibold text-gray-900 text-sm flex-shrink-0">🖼️ Şəkil seçin</span>
            <div class="flex flex-1 gap-2">
              <input
                v-model="imgSearchQuery"
                type="text"
                placeholder="Yenidən axtar..."
                class="flex-1 px-2.5 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-400 outline-none"
                @keyup.enter="retrySearch"
              />
              <button @click="retrySearch" :disabled="imgLoading"
                class="px-3 py-1.5 text-sm bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 disabled:opacity-50 transition">
                🔍
              </button>
            </div>
            <button @click="closeImages" class="text-gray-400 hover:text-gray-600 text-xl leading-none flex-shrink-0">×</button>
          </div>

          <!-- Loading skeletons -->
          <div v-if="imgLoading" class="grid grid-cols-3 gap-2">
            <div v-for="i in 9" :key="i" class="aspect-square bg-gray-100 rounded-xl animate-pulse"></div>
          </div>

          <!-- No images found -->
          <div v-else-if="!suggestedImages.length" class="text-center py-8 text-gray-400">
            <p class="text-3xl mb-2">🔍</p>
            <p class="text-sm mb-3">
              <span v-if="imgError">{{ imgErrorMessages[imgError] || imgError }}</span>
              <span v-else>Nəticə tapılmadı</span>
            </p>
            <p class="text-xs text-gray-400">Yuxarıdakı axtarış qutusundan İngiliscə axtarış cəhd edin</p>
          </div>

          <!-- Images grid — only saving image gets spinner, others stay active -->
          <div v-else class="grid grid-cols-3 gap-2">
            <button
              v-for="(img, i) in suggestedImages"
              :key="i"
              @click="selectImage(img, i)"
              :disabled="savingIndex !== -1"
              class="aspect-square rounded-xl overflow-hidden border-2 transition group relative"
              :class="savingIndex === i
                ? 'border-cyan-500 cursor-wait'
                : savingIndex !== -1
                  ? 'border-transparent opacity-60 cursor-not-allowed'
                  : 'border-transparent hover:border-cyan-500 cursor-pointer'"
            >
              <img :src="img.thumbnail" :alt="img.title"
                class="w-full h-full object-cover transition duration-200"
                :class="savingIndex === -1 ? 'group-hover:scale-105' : ''" />

              <!-- Spinner on the selected image -->
              <div v-if="savingIndex === i"
                class="absolute inset-0 bg-black/40 flex items-center justify-center">
                <svg class="animate-spin h-6 w-6 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
              </div>

              <!-- Hover checkmark -->
              <div v-else-if="savingIndex === -1"
                class="absolute inset-0 bg-black/0 group-hover:bg-black/15 flex items-center justify-center transition">
                <span class="text-white text-xl opacity-0 group-hover:opacity-100 transition">✓</span>
              </div>
            </button>
          </div>

          <p v-if="imgError === 'save_failed'" class="mt-2 text-center text-xs text-red-500">
            Şəkil saxlanarkən xəta. Yenidən cəhd edin.
          </p>

          <button @click="closeImages" class="mt-3 w-full py-2 text-sm text-gray-400 hover:text-gray-600 transition">
            Şəkilsiz davam et →
          </button>
        </div>
      </Transition>

    </div>
  </AppLayout>
</template>

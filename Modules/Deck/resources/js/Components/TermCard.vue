<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import ImagePicker from './ImagePicker.vue';
import SpeakButton from '@/Components/SpeakButton.vue';
import axios from 'axios';

const props = defineProps({ term: Object, deck: Object });
const emit  = defineEmits(['updated', 'deleted']);

const expanded        = ref(false);
const editing         = ref(false);
const showImagePicker = ref(false);
const enriching       = ref(false);
const enrichStatus    = ref('');
const editProcessing  = ref(false);
const editErrors      = ref({});

// Editable form state (synced from props when modal opens)
const form = reactive({
  term:       props.term.term,
  definition: props.term.definition,
  examples:   props.term.examples?.map(e => ({ ...e })) || [],
});

// Local image — updated by ImagePicker or when props change
const currentImage = ref(props.term.primary_image?.url || null);

// Keep image in sync if parent updates the term (e.g. after enrich)
watch(() => props.term.primary_image?.url, (url) => {
  currentImage.value = url || null;
});

// Re-sync form each time edit modal is opened so stale data is never shown
watch(editing, (open) => {
  if (open) {
    form.term       = props.term.term;
    form.definition = props.term.definition;
    form.examples   = props.term.examples?.map(e => ({ ...e })) || [];
    editErrors.value = {};
  }
});

// ── Save edit (axios — no page navigation) ───────────────────────────────
const saveEdit = async () => {
  editProcessing.value = true;
  editErrors.value     = {};
  try {
    const res = await axios.put(`/terms/${props.term.id}`, {
      term:       form.term,
      definition: form.definition,
      examples:   form.examples,
    });
    emit('updated', res.data.term);
    editing.value = false;
  } catch (e) {
    if (e.response?.status === 422) {
      editErrors.value = e.response.data.errors || {};
    }
  } finally {
    editProcessing.value = false;
  }
};

// ── Re-enrich via AI ─────────────────────────────────────────────────────
const reEnrich = async () => {
  enriching.value    = true;
  enrichStatus.value = '';
  try {
    const res = await axios.post(`/terms/${props.term.id}/enrich`);
    const statusMap = {
      ok:     '✓ AI tamamladı',
      quota:  '⏳ OpenAI limit aşıldı, bir az sonra cəhd edin',
      no_key: '⚠ OPENAI API key tapılmadı (.env-ə OPENAI_API_KEY=sk-... yazın)',
      error:  '✕ AI xətası, yenidən cəhd edin',
    };
    enrichStatus.value = statusMap[res.data.status] || '✕ Naməlum xəta';
    if (res.data.success && res.data.term) {
      emit('updated', res.data.term);
      // Sync local form examples so re-opening edit shows fresh data
      form.examples = (res.data.term.examples || []).map(e => ({
        id:          e.id,
        sentence:    e.sentence,
        translation: e.translation || '',
      }));
    }
  } catch {
    enrichStatus.value = '✕ Xəta baş verdi';
  } finally {
    enriching.value = false;
  }
};

// ── Delete (axios — no page navigation) ─────────────────────────────────
const deleteTerm = async () => {
  if (!confirm('Bu söz silinsin?')) return;
  try {
    await axios.delete(`/terms/${props.term.id}`);
    emit('deleted', props.term.id);
  } catch {
    alert('Silmə zamanı xəta baş verdi. Yenidən cəhd edin.');
  }
};

// ── Image selected ───────────────────────────────────────────────────────
const onImageSelected = (imageUrl) => {
  currentImage.value    = imageUrl;
  showImagePicker.value = false;
};

// ── Gender badge style ───────────────────────────────────────────────────
const genderClass = computed(() => ({
  'bg-blue-100 text-blue-700':     props.term.gender === 'der',
  'bg-pink-100 text-pink-700':     props.term.gender === 'die',
  'bg-yellow-100 text-yellow-700': props.term.gender === 'das',
}));

const addExample    = () => form.examples.push({ sentence: '', translation: '' });
const removeExample = (i) => form.examples.splice(i, 1);
</script>

<template>
  <div class="bg-white rounded-2xl border border-gray-200 hover:border-gray-300 transition">

    <!-- Term row -->
    <div class="flex items-center gap-3 p-4 cursor-pointer" @click="expanded = !expanded">

      <!-- Image thumbnail -->
      <div class="w-12 h-12 rounded-xl overflow-hidden bg-gray-100 flex-shrink-0">
        <img v-if="currentImage" :src="currentImage" class="w-full h-full object-cover" :alt="term.term" />
        <button v-else @click.stop="showImagePicker = true"
          class="w-full h-full flex items-center justify-center text-gray-300 hover:text-indigo-400 hover:bg-indigo-50 transition text-xl">
          🖼️
        </button>
      </div>

      <!-- Term info -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-1.5 flex-wrap">
          <span v-if="term.gender" class="text-xs font-bold px-1.5 py-0.5 rounded" :class="genderClass">
            {{ term.gender }}
          </span>
          <span class="font-semibold text-gray-900">{{ term.term }}</span>
          <span v-if="term.plural_form" class="text-sm text-gray-400">/ {{ term.plural_form }}</span>
          <span v-if="term.pronunciation" class="text-xs text-indigo-500 font-mono">/{{ term.pronunciation }}/</span>
          <SpeakButton :text="term.term" :lang="deck.source_language?.code || 'de'" size="sm" />
        </div>
        <p class="text-sm text-gray-500 truncate">{{ term.definition }}</p>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-1 flex-shrink-0">
        <span v-if="term.part_of_speech" class="hidden sm:block text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">
          {{ { noun:'İsim', verb:'Fel', adjective:'Sifət', adverb:'Zərf', phrase:'İfadə', other:'Digər' }[term.part_of_speech] }}
        </span>
        <button @click.stop="editing = true"
          class="p-1.5 text-gray-400 hover:text-indigo-600 transition rounded-lg hover:bg-indigo-50">✏️</button>
        <button @click.stop="deleteTerm"
          class="p-1.5 text-gray-400 hover:text-red-500 transition rounded-lg hover:bg-red-50">🗑️</button>
        <span class="text-gray-300 text-sm ml-1">{{ expanded ? '▲' : '▼' }}</span>
      </div>
    </div>

    <!-- Expanded: examples -->
    <div v-if="expanded && (term.examples?.length || term.notes)"
      class="px-4 pb-4 border-t border-gray-50 pt-3">
      <div v-if="term.notes" class="text-sm text-gray-500 italic mb-2">{{ term.notes }}</div>
      <div v-if="term.examples?.length">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Nümunələr</p>
        <div v-for="(ex, i) in term.examples" :key="i"
          class="text-sm py-1.5 border-b border-gray-50 last:border-0">
          <p class="text-gray-800">{{ ex.sentence }}</p>
          <p v-if="ex.translation" class="text-gray-400 text-xs mt-0.5">{{ ex.translation }}</p>
        </div>
      </div>
    </div>

  </div>

  <!-- Edit Modal -->
  <Teleport to="body">
    <div v-if="editing"
      class="fixed inset-0 bg-black/50 flex items-start justify-center z-50 p-4 overflow-y-auto">
      <div class="bg-white rounded-2xl w-full max-w-lg my-8">

        <div class="flex items-center justify-between p-5 border-b border-gray-100">
          <h3 class="font-semibold text-gray-900">Söz Düzəliş</h3>
          <button @click="editing = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">×</button>
        </div>

        <form @submit.prevent="saveEdit" class="p-5 space-y-4">

          <!-- Image -->
          <div class="flex items-center gap-3">
            <div class="w-14 h-14 rounded-xl overflow-hidden bg-gray-100 flex-shrink-0">
              <img v-if="currentImage" :src="currentImage" class="w-full h-full object-cover" />
              <div v-else class="w-full h-full flex items-center justify-center text-gray-300 text-2xl">🖼️</div>
            </div>
            <button type="button" @click="showImagePicker = true"
              class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition">
              {{ currentImage ? 'Şəkli dəyiş' : 'Şəkil əlavə et' }}
            </button>
          </div>

          <!-- Term + Definition -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Söz</label>
              <input v-model="form.term" type="text" required
                :class="['w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none',
                         editErrors.term ? 'border-red-400' : 'border-gray-300']" />
              <p v-if="editErrors.term" class="text-red-500 text-xs mt-1">{{ editErrors.term[0] }}</p>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Tərcümə</label>
              <input v-model="form.definition" type="text" required
                :class="['w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none',
                         editErrors.definition ? 'border-red-400' : 'border-gray-300']" />
              <p v-if="editErrors.definition" class="text-red-500 text-xs mt-1">{{ editErrors.definition[0] }}</p>
            </div>
          </div>

          <!-- AI-filled fields (read-only display) -->
          <div v-if="term.pronunciation || term.gender || term.plural_form || term.part_of_speech"
            class="p-3 bg-gray-50 rounded-xl text-sm text-gray-600 space-y-1">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">✨ AI tərəfindən doldurulub</p>
            <div class="flex flex-wrap gap-2">
              <span v-if="term.pronunciation" class="font-mono text-indigo-600">/{{ term.pronunciation }}/</span>
              <span v-if="term.gender" class="font-bold px-2 py-0.5 rounded text-xs" :class="genderClass">{{ term.gender }}</span>
              <span v-if="term.plural_form" class="text-gray-600">{{ term.plural_form }}</span>
              <span v-if="term.part_of_speech" class="bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full text-xs">
                {{ { noun:'İsim', verb:'Fel', adjective:'Sifət', adverb:'Zərf', phrase:'İfadə', other:'Digər' }[term.part_of_speech] }}
              </span>
            </div>
          </div>

          <!-- Re-enrich button -->
          <button type="button" @click="reEnrich" :disabled="enriching"
            class="w-full py-2 border border-indigo-300 text-indigo-600 rounded-xl text-sm font-medium hover:bg-indigo-50 disabled:opacity-50 transition flex items-center justify-center gap-2">
            <svg v-if="enriching" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
            <span>{{ enriching ? 'AI zənglənir...' : '✨ AI ilə yenidən doldur' }}</span>
          </button>
          <p v-if="enrichStatus" class="text-center text-xs" :class="enrichStatus.startsWith('✓') ? 'text-green-600' : 'text-orange-500'">
            {{ enrichStatus }}
          </p>

          <!-- Examples (editable) -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="text-xs font-medium text-gray-600">Nümunə cümlələr</label>
              <button type="button" @click="addExample" class="text-xs text-indigo-600 hover:underline">+ Əlavə et</button>
            </div>
            <div v-for="(ex, i) in form.examples" :key="i" class="flex gap-2 mb-2">
              <div class="flex-1 space-y-1">
                <input v-model="ex.sentence" type="text" placeholder="Nümunə..."
                  class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-sm outline-none focus:ring-1 focus:ring-indigo-400" />
                <input v-model="ex.translation" type="text" placeholder="Tərcümə..."
                  class="w-full px-2.5 py-1.5 border border-gray-200 bg-gray-50 rounded-lg text-sm outline-none focus:ring-1 focus:ring-indigo-400" />
              </div>
              <button type="button" @click="removeExample(i)" class="text-gray-400 hover:text-red-500 self-start pt-1.5">✕</button>
            </div>
          </div>

          <!-- Buttons -->
          <div class="flex gap-3 pt-1">
            <button type="button" @click="editing = false"
              class="flex-1 py-2.5 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50 transition text-sm">
              Ləğv et
            </button>
            <button type="submit" :disabled="editProcessing"
              class="flex-1 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 disabled:opacity-50 transition text-sm font-medium">
              {{ editProcessing ? 'Saxlanır...' : 'Saxla' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <ImagePicker
      v-if="showImagePicker"
      :term-id="term.id"
      :initial-query="term.term"
      :lang="deck.source_language?.code || 'en'"
      @close="showImagePicker = false"
      @selected="onImageSelected"
    />
  </Teleport>
</template>

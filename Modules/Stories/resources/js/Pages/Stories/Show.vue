<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'


const props = defineProps({
    story: Object,
    knownWords: Array,
})

// ── Tokenizer ────────────────────────────────────────────────────────────────
function tokenize(text) {
    const tokens = []
    const regex = /([a-zA-ZäöüÄÖÜßÀ-ɏ]+)|([^a-zA-ZäöüÄÖÜßÀ-ɏ]+)/g
    let match
    while ((match = regex.exec(text)) !== null) {
        tokens.push({ text: match[0], isWord: !!match[1] })
    }
    return tokens
}

function htmlToPlain(html) {
    return html
        // Boş abzaslar (CKEditor-un blank line-ları) → real paragraph break
        .replace(/<p>\s*(&nbsp;|<br\s*\/?>)?\s*<\/p>/gi, '\n\n')
        // Adi abzas keçidi → boşluq (bunlar orijinal sətir qırıqlarıdır, ayrı abzas deyil)
        .replace(/<\/p>\s*<p>/gi, ' ')
        .replace(/<br\s*\/?>/gi, '\n')
        .replace(/<[^>]+>/g, ' ')
        .replace(/&nbsp;/g, ' ')
        .replace(/&amp;/g, '&')
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>')
        .replace(/ ([,\.;:!?»])/g, '$1')  // vergül/nöqtə öncəsi əlavə boşluğu sil
        .replace(/[ \t]+/g, ' ')
        .replace(/^[ \t]+|[ \t]+$/gm, '')
        .replace(/\n{3,}/g, '\n\n')
        .trim()
}

// Split body into paragraphs, tokenize each paragraph separately
const paragraphs = computed(() =>
    htmlToPlain(props.story.body)
        .split(/\r?\n/)
        .map(line => ({ tokens: tokenize(line), empty: line.trim() === '' }))
)

// ── Word normalization (mirrors backend Term::normalize) ──────────────────────
function normalize(word) {
    return word
        .toLowerCase()
        .trim()
        .replace(/^(der|die|das|ein|eine|einen|einem|einer|eines|le|la|les|el|los|las|il|lo|gli|un|une|a|an|the)\s+/i, '')
        .trim()
}

const knownSet = reactive(new Set(props.knownWords || []))

// Mirrors backend prefix-match logic:
// "herausforderungen" → matches stored "herausforderung" (prefix, diff 1-4, min length 5)
const isKnown = (word) => {
    const n = normalize(word)
    if (knownSet.has(n)) return true
    for (const known of knownSet) {
        if (known.length >= 5 && n.startsWith(known) && n.length - known.length <= 4 && n.length > known.length) return true
    }
    return false
}

// ── Modal — each piece of state is its own ref, independently tracked by Vue ──
const mVisible    = ref(false)
const mWord       = ref('')
const mLoading    = ref(false)
const mFound      = ref(false)
const mTerm       = ref(null)
const mTranslating = ref(false)
const mTranslated = ref(null)
const mAdding     = ref(false)
const mError      = ref('')

function resetModal(word) {
    mVisible.value    = true
    mWord.value       = word
    mLoading.value    = true
    mFound.value      = false
    mTerm.value       = null
    mTranslating.value = false
    mTranslated.value = null
    mAdding.value     = false
    mError.value      = ''
}

async function onWordClick(word) {
    const w = word.trim()
    if (!w || w.length < 2) return

    // Eyni söz üçün translate gözlənilirsə — modal-ı sadəcə aç, state-i sıfırlama
    if (mTranslating.value && mWord.value === w) {
        mVisible.value = true
        return
    }

    if (mVisible.value && mWord.value === w) {
        mVisible.value = false
        return
    }

    resetModal(w)

    try {
        const res = await axios.post(`/stories/${props.story.id}/lookup-word`, { word: w })
        if (mWord.value !== w) return
        mFound.value = res.data.found
        mTerm.value  = res.data.term ?? null
    } catch {
        mError.value = 'Xəta baş verdi'
    } finally {
        if (mWord.value === w) mLoading.value = false
    }
}

async function translateWord() {
    const word = mWord.value
    mTranslating.value = true
    mError.value = ''
    try {
        const res = await axios.post(`/stories/${props.story.id}/translate-word`, { word })
        if (mWord.value !== word) return
        const data = res.data.data ?? null
        mTranslated.value = data
        mError.value = data ? '' : (res.data.message || 'Tərcümə alınmadı')
        // Cavab gəldikdə modal bağlanmışsa — yenidən aç
        mVisible.value = true
    } catch (e) {
        mError.value = e.response?.data?.message || 'AI xətası'
        if (mWord.value === word) mVisible.value = true
    } finally {
        if (mWord.value === word) mTranslating.value = false
    }
}

async function addWord() {
    const d = mTranslated.value
    if (!d) return
    const word = mWord.value
    mAdding.value = true
    mError.value  = ''
    try {
        const res = await axios.post(`/stories/${props.story.id}/add-word`, {
            term:           d.term || word,
            definition:     d.definition,
            gender:         d.gender || null,
            part_of_speech: d.part_of_speech || null,
        })
        knownSet.add(res.data.normalized || normalize(d.term || word))
        mVisible.value = false
    } catch (e) {
        mError.value = e.response?.data?.error || 'Xəta baş verdi'
    } finally {
        mAdding.value = false
    }
}

function closeModal() { mVisible.value = false }

function handleOutside(e) {
    if (mTranslating.value) return  // AI cavabı gözlənilir — modal bağlanmasın
    if (!e.target.closest('.word-modal') && !e.target.closest('.word-token')) {
        mVisible.value = false
    }
}

onMounted(() => document.addEventListener('click', handleOutside))
onUnmounted(() => document.removeEventListener('click', handleOutside))

const levelColors = {
    A1: 'bg-green-100 text-green-700',
    A2: 'bg-emerald-100 text-emerald-700',
    B1: 'bg-blue-100 text-blue-700',
    B2: 'bg-cyan-100 text-cyan-700',
    C1: 'bg-purple-100 text-purple-700',
    C2: 'bg-rose-100 text-rose-700',
}
</script>

<template>
    <AppLayout :title="story.title">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- Header -->
            <div class="flex items-center gap-3 mb-6">
                <button @click="router.visit('/stories')" class="text-gray-400 hover:text-gray-600 transition text-xl leading-none">←</button>
                <div class="flex-1 min-w-0">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900 truncate">{{ story.title }}</h1>
                    <div class="flex items-center gap-3 mt-1 flex-wrap">
                        <span
                            v-if="story.level"
                            class="text-xs px-2 py-0.5 rounded-full font-medium"
                            :class="levelColors[story.level] || 'bg-gray-100 text-gray-600'"
                        >{{ story.level }}</span>
                        <span v-if="story.deck" class="text-xs text-gray-500">
                            Dəst:
                            <button @click="router.visit(`/decks/${story.deck.id}`)" class="text-cyan-600 hover:underline">{{ story.deck.title }}</button>
                        </span>
                        <span class="text-xs text-gray-400 hidden sm:inline">· Sözə klik → tərcümə</span>
                    </div>
                </div>
                <button
                    @click="router.visit(`/stories/${story.id}/edit`)"
                    class="shrink-0 px-4 py-2 bg-white border border-gray-200 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-50 hover:border-gray-300 transition"
                >Düzəlt</button>
            </div>

            <!-- Audio Player -->
            <div v-if="story.audio_url" class="mb-4 bg-white rounded-2xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400 mb-2 font-medium">Audio</p>
                <audio :src="story.audio_url" controls class="w-full rounded-lg"></audio>
            </div>

            <!-- Legend -->
            <div class="flex items-center gap-5 mb-4 text-xs text-gray-600">
                <span class="flex items-center gap-1.5">
                    <span class="underline decoration-green-500 decoration-2 underline-offset-2 font-medium">Wort</span>
                    Dəstdə var ({{ knownSet.size }} söz)
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-4 h-4 rounded bg-cyan-50 border border-cyan-200"></span>
                    Hover / klikdə vurğulanır
                </span>
            </div>

            <!-- Story body -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6 text-gray-800 text-[17px] leading-[1.9]">
                <template v-for="(line, li) in paragraphs" :key="li">
                    <!-- Empty line → paragraph break -->
                    <div v-if="line.empty" class="h-3"></div>

                    <!-- Text line -->
                    <span v-else>
                        <template v-for="(token, ti) in line.tokens" :key="ti">
                            <span
                                v-if="token.isWord"
                                class="word-token cursor-pointer transition-colors"
                                :class="[
                                    isKnown(token.text)
                                        ? 'underline decoration-green-500 decoration-2 underline-offset-2 hover:decoration-green-400'
                                        : 'hover:bg-cyan-100 rounded-sm px-[1px]',
                                    mVisible && mWord === token.text.trim()
                                        ? '!bg-cyan-500/20 ring-1 ring-cyan-400 rounded px-[1px]'
                                        : ''
                                ]"
                                @click="onWordClick(token.text)"
                            >{{ token.text }}</span>
                            <span v-else>{{ token.text }}</span>
                        </template>
                    </span>
                </template>
            </div>
        </div>

        <!-- ── Word Lookup Modal ──────────────────────────────────────────── -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="mVisible"
                    class="fixed inset-0 bg-black/40 z-50 flex items-end sm:items-center justify-center p-4"
                    @click.self="closeModal"
                >
                    <div class="word-modal bg-white rounded-2xl shadow-2xl w-full max-w-sm p-5 relative">
                        <button @click="closeModal" class="absolute top-4 right-4 text-gray-300 hover:text-gray-600 text-2xl leading-none">×</button>

                        <h3 class="text-2xl font-bold text-gray-900 mb-4 pr-8">{{ mWord }}</h3>

                        <!-- DB lookup spinner -->
                        <div v-if="mLoading" class="py-6 text-center">
                            <div class="inline-block w-5 h-5 border-2 border-gray-200 border-t-cyan-500 rounded-full animate-spin"></div>
                        </div>

                        <!-- FOUND IN DECK -->
                        <div v-else-if="mFound && mTerm" class="space-y-3">
                            <div class="flex flex-wrap gap-2">
                                <span v-if="mTerm.gender" class="text-xs bg-blue-100 text-blue-700 px-2.5 py-1 rounded-full font-semibold">{{ mTerm.gender }}</span>
                                <span v-if="mTerm.part_of_speech" class="text-xs bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full capitalize">{{ mTerm.part_of_speech }}</span>
                                <span v-if="mTerm.pronunciation" class="text-xs text-gray-500 font-mono bg-gray-50 px-2 py-1 rounded">/{{ mTerm.pronunciation }}/</span>
                            </div>
                            <p class="text-cyan-700 font-bold text-xl">{{ mTerm.definition }}</p>
                            <p v-if="mTerm.plural_form" class="text-sm text-gray-500">Cəm: <span class="font-medium text-gray-700">{{ mTerm.plural_form }}</span></p>
                            <div v-if="mTerm.examples?.length" class="bg-gray-50 rounded-xl p-3 space-y-2">
                                <div v-for="ex in mTerm.examples" :key="ex.id" class="text-sm">
                                    <p class="italic text-gray-700">{{ ex.sentence }}</p>
                                    <p class="text-gray-500 text-xs mt-0.5">{{ ex.translation }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 pt-1 border-t border-gray-100">
                                <span class="text-xs font-medium text-green-600">✓ Dəstdədir</span>
                                <span class="text-xs text-gray-400">— {{ story.deck?.title }}</span>
                            </div>
                        </div>

                        <!-- NOT FOUND + not yet translated -->
                        <div v-else-if="!mFound && !mTranslated">
                            <p class="text-gray-400 text-sm mb-4">Bu söz hekayənin dəstindən tapılmadı.</p>
                            <p v-if="mError" class="text-xs text-red-500 mb-2">{{ mError }}</p>
                            <button
                                @click="translateWord"
                                :disabled="mTranslating"
                                class="w-full py-2.5 bg-cyan-600 text-white rounded-xl font-medium hover:bg-cyan-700 disabled:opacity-50 transition text-sm"
                            >
                                <span v-if="mTranslating" class="flex items-center justify-center gap-2">
                                    <span class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                                    Tərcümə edilir...
                                </span>
                                <span v-else>AI ilə Tərcümə Et</span>
                            </button>
                        </div>

                        <!-- NOT FOUND + translated -->
                        <div v-else-if="!mFound && mTranslated">
                            <div class="space-y-2 mb-4">
                                <div class="flex flex-wrap gap-2">
                                    <span v-if="mTranslated.gender" class="text-xs bg-blue-100 text-blue-700 px-2.5 py-1 rounded-full font-semibold">{{ mTranslated.gender }}</span>
                                    <span v-if="mTranslated.part_of_speech" class="text-xs bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full capitalize">{{ mTranslated.part_of_speech }}</span>
                                </div>
                                <p class="text-sm text-gray-500 font-medium">{{ mTranslated.term }}</p>
                                <p class="text-cyan-700 font-bold text-xl">{{ mTranslated.definition }}</p>
                            </div>
                            <p v-if="mError" class="text-xs text-red-500 mb-2">{{ mError }}</p>
                            <button
                                v-if="story.deck_id"
                                @click="addWord"
                                :disabled="mAdding"
                                class="w-full py-2.5 bg-cyan-600 text-white rounded-xl font-medium hover:bg-cyan-700 disabled:opacity-50 transition text-sm"
                            >{{ mAdding ? 'Əlavə edilir...' : '+ Dəstə Əlavə Et' }}</button>
                            <p v-else class="text-xs text-gray-400 text-center mt-2">Dəstə əlavə etmək üçün hekayəyə dəst seçin.</p>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.15s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>

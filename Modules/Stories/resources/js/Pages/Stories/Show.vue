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

// Split body into paragraphs, tokenize each paragraph separately
const paragraphs = computed(() =>
    props.story.body
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

// ── Modal state — ref() + spread guarantees Vue detects every change ─────────
const MODAL_DEFAULT = {
    visible: false, word: '', loading: false,
    found: false, term: null,
    translating: false, translated: null,
    adding: false, error: '',
}
const modal = ref({ ...MODAL_DEFAULT })
const m = (updates) => { modal.value = { ...modal.value, ...updates } }

async function onWordClick(word) {
    const w = word.trim()
    if (!w || w.length < 2) return

    if (modal.value.visible && modal.value.word === w) {
        m({ visible: false })
        return
    }

    m({ ...MODAL_DEFAULT, visible: true, word: w, loading: true })

    try {
        const res = await axios.post(`/stories/${props.story.id}/lookup-word`, { word: w })
        m({ found: res.data.found, term: res.data.term ?? null })
    } catch {
        m({ error: 'Xəta baş verdi' })
    } finally {
        m({ loading: false })
    }
}

async function translateWord() {
    const word = modal.value.word
    m({ translating: true, error: '' })
    try {
        const res = await axios.post(`/stories/${props.story.id}/translate-word`, { word })
        const data = res.data.data ?? null
        m({ translated: data, error: data ? '' : (res.data.message || 'Tərcümə alınmadı') })
    } catch (e) {
        m({ error: e.response?.data?.message || 'AI xətası' })
    } finally {
        m({ translating: false })
    }
}

async function addWord() {
    const d = modal.value.translated
    if (!d) return
    const word = modal.value.word
    m({ adding: true, error: '' })
    try {
        const res = await axios.post(`/stories/${props.story.id}/add-word`, {
            term:           d.term || word,
            definition:     d.definition,
            gender:         d.gender || null,
            part_of_speech: d.part_of_speech || null,
        })
        knownSet.add(res.data.normalized || normalize(d.term || word))
        m({ visible: false })
    } catch (e) {
        m({ error: e.response?.data?.error || 'Xəta baş verdi' })
    } finally {
        m({ adding: false })
    }
}

function closeModal() { m({ visible: false }) }

function handleOutside(e) {
    if (!e.target.closest('.word-modal') && !e.target.closest('.word-token')) {
        m({ visible: false })
    }
}

onMounted(() => document.addEventListener('click', handleOutside))
onUnmounted(() => document.removeEventListener('click', handleOutside))

const levelColors = {
    A1: 'bg-green-100 text-green-700',
    A2: 'bg-emerald-100 text-emerald-700',
    B1: 'bg-blue-100 text-blue-700',
    B2: 'bg-indigo-100 text-indigo-700',
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
                            <button @click="router.visit(`/decks/${story.deck.id}`)" class="text-indigo-600 hover:underline">{{ story.deck.title }}</button>
                        </span>
                        <button
                            @click="router.visit(`/stories/${story.id}/edit`)"
                            class="text-xs text-gray-400 hover:text-indigo-600 transition"
                        >Düzəlt</button>
                        <span class="text-xs text-gray-400 hidden sm:inline">· Sözə klik → tərcümə</span>
                    </div>
                </div>
            </div>

            <!-- Legend -->
            <div class="flex items-center gap-5 mb-4 text-xs text-gray-500">
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-4 h-4 rounded bg-green-100 border border-green-300"></span>
                    Dəstdə var ({{ knownSet.size }} söz)
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-4 h-4 rounded bg-indigo-50 border border-indigo-200"></span>
                    Hover / klikdə vurğulanır
                </span>
            </div>

            <!-- Story body -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6 text-gray-800 text-[17px] leading-8">
                <template v-for="(line, li) in paragraphs" :key="li">
                    <!-- Empty line → paragraph break -->
                    <div v-if="line.empty" class="h-4"></div>

                    <!-- Text line -->
                    <span v-else>
                        <template v-for="(token, ti) in line.tokens" :key="ti">
                            <span
                                v-if="token.isWord"
                                class="word-token cursor-pointer rounded-sm px-[1px] transition-colors"
                                :class="[
                                    isKnown(token.text)
                                        ? 'bg-green-100 hover:bg-green-200'
                                        : 'hover:bg-indigo-100',
                                    modal.visible && modal.word === token.text.trim()
                                        ? '!bg-indigo-200 ring-1 ring-indigo-400 rounded'
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
                    v-if="modal.visible"
                    class="fixed inset-0 bg-black/40 z-50 flex items-end sm:items-center justify-center p-4"
                    @click.self="closeModal"
                >
                    <div class="word-modal bg-white rounded-2xl shadow-2xl w-full max-w-sm p-5 relative">
                        <!-- Close -->
                        <button
                            @click="closeModal"
                            class="absolute top-4 right-4 text-gray-300 hover:text-gray-600 text-2xl leading-none"
                        >×</button>

                        <!-- Word heading -->
                        <h3 class="text-2xl font-bold text-gray-900 mb-4 pr-8">{{ modal.word }}</h3>

                        <!-- Loading spinner (DB lookup) -->
                        <div v-if="modal.loading" class="py-6 text-center">
                            <div class="inline-block w-5 h-5 border-2 border-gray-200 border-t-indigo-500 rounded-full animate-spin"></div>
                        </div>

                        <!-- ── FOUND IN DECK ── -->
                        <template v-else-if="modal.found && modal.term">
                            <div class="space-y-3">
                                <div class="flex flex-wrap gap-2">
                                    <span v-if="modal.term.gender" class="text-xs bg-blue-100 text-blue-700 px-2.5 py-1 rounded-full font-semibold">{{ modal.term.gender }}</span>
                                    <span v-if="modal.term.part_of_speech" class="text-xs bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full capitalize">{{ modal.term.part_of_speech }}</span>
                                    <span v-if="modal.term.pronunciation" class="text-xs text-gray-500 font-mono bg-gray-50 px-2 py-1 rounded">/{{ modal.term.pronunciation }}/</span>
                                </div>
                                <p class="text-indigo-700 font-bold text-xl">{{ modal.term.definition }}</p>
                                <p v-if="modal.term.plural_form" class="text-sm text-gray-500">Cəm: <span class="font-medium text-gray-700">{{ modal.term.plural_form }}</span></p>
                                <div v-if="modal.term.examples?.length" class="bg-gray-50 rounded-xl p-3 space-y-2">
                                    <div v-for="ex in modal.term.examples" :key="ex.id" class="text-sm">
                                        <p class="italic text-gray-700">{{ ex.sentence }}</p>
                                        <p class="text-gray-500 text-xs mt-0.5">{{ ex.translation }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 pt-1 border-t border-gray-100">
                                    <span class="text-xs font-medium text-green-600">✓ Dəstdədir</span>
                                    <span class="text-xs text-gray-400">— {{ story.deck?.title }}</span>
                                </div>
                            </div>
                        </template>

                        <!-- ── NOT FOUND: before translate ── -->
                        <div v-if="!modal.loading && !modal.found && !modal.translated">
                            <p class="text-gray-400 text-sm mb-4">Bu söz hekayənin dəstindən tapılmadı.</p>
                            <p v-if="modal.error" class="text-xs text-red-500 mb-2">{{ modal.error }}</p>
                            <button
                                @click="translateWord"
                                :disabled="modal.translating"
                                class="w-full py-2.5 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 disabled:opacity-50 transition text-sm"
                            >
                                <span v-if="modal.translating" class="flex items-center justify-center gap-2">
                                    <span class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                                    Tərcümə edilir...
                                </span>
                                <span v-else>AI ilə Tərcümə Et</span>
                            </button>
                        </div>

                        <!-- ── NOT FOUND: after translate ── -->
                        <div v-if="!modal.loading && !modal.found && modal.translated">
                            <div class="space-y-2 mb-4">
                                <div class="flex flex-wrap gap-2">
                                    <span v-if="modal.translated.gender" class="text-xs bg-blue-100 text-blue-700 px-2.5 py-1 rounded-full font-semibold">{{ modal.translated.gender }}</span>
                                    <span v-if="modal.translated.part_of_speech" class="text-xs bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full capitalize">{{ modal.translated.part_of_speech }}</span>
                                </div>
                                <p class="text-sm text-gray-500 font-medium">{{ modal.translated.term }}</p>
                                <p class="text-indigo-700 font-bold text-xl">{{ modal.translated.definition }}</p>
                            </div>
                            <p v-if="modal.error" class="text-xs text-red-500 mb-2">{{ modal.error }}</p>
                            <button
                                v-if="story.deck_id"
                                @click="addWord"
                                :disabled="modal.adding"
                                class="w-full py-2.5 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 disabled:opacity-50 transition text-sm"
                            >{{ modal.adding ? 'Əlavə edilir...' : '+ Dəstə Əlavə Et' }}</button>
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

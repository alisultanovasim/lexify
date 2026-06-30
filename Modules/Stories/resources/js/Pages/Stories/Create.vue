<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { Ckeditor } from '@ckeditor/ckeditor5-vue'
import ClassicEditor from '@ckeditor/ckeditor5-build-classic'

const props = defineProps({
    languages: Array,
    decks: Array,
})

const form = useForm({
    title: '',
    body: '',
    level: '',
    deck_id: null,
    language_id: null,
    audio: null,
})

const levels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2']

const editorConfig = {
    toolbar: ['bold', 'italic', '|', 'bulletedList', 'numberedList', '|', 'undo', 'redo'],
}

const wordCount = computed(() =>
    form.body.replace(/<[^>]+>/g, ' ').trim().split(/\s+/).filter(Boolean).length
)

const submit = () => {
    if (!form.body.replace(/<[^>]+>/g, '').trim()) {
        form.setError('body', 'Mətn daxil edin')
        return
    }
    form.post('/stories', { forceFormData: true })
}

function onAudioChange(e) {
    form.audio = e.target.files[0] ?? null
}
</script>

<template>
    <AppLayout title="Yeni Hekayə">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Back -->
            <div class="flex items-center gap-3 mb-8">
                <button @click="router.visit('/stories')" class="text-gray-400 hover:text-gray-600 transition">←</button>
                <h1 class="text-2xl font-bold text-gray-900">Yeni Hekayə Əlavə Et</h1>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Title -->
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Başlıq *</label>
                    <input
                        v-model="form.title"
                        type="text"
                        placeholder="Məs: Die Reise nach Berlin"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent outline-none"
                        required
                    />
                    <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
                </div>

                <!-- Body -->
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mətn *</label>
                    <p class="text-xs text-gray-400 mb-3">Alman mətnini buraya yapışdırın. Hər söz kliklanabilir olacaq.</p>
                    <div class="ck-wrapper">
                        <Ckeditor :editor="ClassicEditor" v-model="form.body" :config="editorConfig" />
                    </div>
                    <div class="flex justify-between mt-2">
                        <p v-if="form.errors.body" class="text-red-500 text-xs">{{ form.errors.body }}</p>
                        <p class="text-xs text-gray-400 ml-auto">{{ wordCount }} söz</p>
                    </div>
                </div>

                <!-- Level + Language -->
                <div class="bg-white rounded-2xl border border-gray-200 p-5 grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Səviyyə</label>
                        <select
                            v-model="form.level"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none"
                        >
                            <option value="">Seçin...</option>
                            <option v-for="l in levels" :key="l" :value="l">{{ l }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dil</label>
                        <select
                            v-model="form.language_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none"
                        >
                            <option :value="null">Seçin...</option>
                            <option v-for="lang in languages" :key="lang.id" :value="lang.id">
                                {{ lang.flag_emoji }} {{ lang.native_name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Deck link -->
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Söz Dəsti (İxtiyari)</label>
                    <p class="text-xs text-gray-400 mb-2">Hekayəyə aid bir dəst seçin — oxuyarkən bilmədiyiniz sözləri bu dəstə əlavə edə bilərsiniz.</p>
                    <select
                        v-model="form.deck_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none"
                    >
                        <option :value="null">Dəstsiz</option>
                        <option v-for="deck in decks" :key="deck.id" :value="deck.id">
                            {{ deck.title }}
                        </option>
                    </select>
                </div>

                <!-- Audio -->
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Audio Fayl <span class="text-gray-400 font-normal">(İxtiyari)</span></label>
                    <p class="text-xs text-gray-400 mb-2">Hekayəyə aid audio yazısını əlavə edin. Show səhifəsində oxunacaq.</p>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <span class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition">Fayl Seç</span>
                        <span class="text-sm text-gray-500 truncate max-w-xs">
                            {{ form.audio ? form.audio.name : 'Fayl seçilməyib' }}
                        </span>
                        <input type="file" accept="audio/*" class="hidden" @change="onAudioChange" />
                    </label>
                    <p v-if="form.errors.audio" class="text-red-500 text-xs mt-1">{{ form.errors.audio }}</p>
                </div>

                <!-- Submit -->
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full py-3 px-4 bg-cyan-600 text-white rounded-xl font-medium hover:bg-cyan-700 disabled:opacity-50 transition"
                >
                    {{ form.processing ? 'Saxlanılır...' : 'Hekayəni Saxla' }}
                </button>
            </form>
        </div>
    </AppLayout>
</template>

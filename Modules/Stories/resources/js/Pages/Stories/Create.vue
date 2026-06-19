<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, router } from '@inertiajs/vue3'

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
})

const levels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2']

const submit = () => form.post('/stories')
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
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none"
                        required
                    />
                    <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
                </div>

                <!-- Body -->
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mətn *</label>
                    <p class="text-xs text-gray-400 mb-2">Alman mətnini buraya yapışdırın. Hər söz kliklanabilir olacaq.</p>
                    <textarea
                        v-model="form.body"
                        rows="12"
                        placeholder="Es war ein schöner Tag in Berlin..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none resize-y font-serif text-base leading-relaxed"
                        required
                    ></textarea>
                    <div class="flex justify-between mt-1">
                        <p v-if="form.errors.body" class="text-red-500 text-xs">{{ form.errors.body }}</p>
                        <p class="text-xs text-gray-400 ml-auto">{{ form.body.trim().split(/\s+/).filter(Boolean).length }} söz</p>
                    </div>
                </div>

                <!-- Level + Language -->
                <div class="bg-white rounded-2xl border border-gray-200 p-5 grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Səviyyə</label>
                        <select
                            v-model="form.level"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                        >
                            <option value="">Seçin...</option>
                            <option v-for="l in levels" :key="l" :value="l">{{ l }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dil</label>
                        <select
                            v-model="form.language_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
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
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                    >
                        <option :value="null">Dəstsiz</option>
                        <option v-for="deck in decks" :key="deck.id" :value="deck.id">
                            {{ deck.title }}
                        </option>
                    </select>
                </div>

                <!-- Submit -->
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full py-3 px-4 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 disabled:opacity-50 transition"
                >
                    {{ form.processing ? 'Saxlanılır...' : 'Hekayəni Saxla' }}
                </button>
            </form>
        </div>
    </AppLayout>
</template>

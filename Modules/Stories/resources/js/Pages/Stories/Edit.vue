<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, router } from '@inertiajs/vue3'

const props = defineProps({
    story: Object,
    languages: Array,
    decks: Array,
})

const form = useForm({
    title:       props.story.title,
    level:       props.story.level ?? '',
    deck_id:     props.story.deck_id ?? null,
    language_id: props.story.language_id ?? null,
})

const levels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2']

const submit = () => form.put(`/stories/${props.story.id}`)
</script>

<template>
    <AppLayout title="Hekayəni Düzəlt">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center gap-3 mb-8">
                <button @click="router.visit(`/stories/${story.id}`)" class="text-gray-400 hover:text-gray-600 transition">←</button>
                <h1 class="text-2xl font-bold text-gray-900">Hekayəni Düzəlt</h1>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Title -->
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Başlıq *</label>
                    <input
                        v-model="form.title"
                        type="text"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none"
                        required
                    />
                    <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
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

                <!-- Deck -->
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Söz Dəsti</label>
                    <p class="text-xs text-gray-400 mb-2">Hekayəyə aid dəsti buradan dəyişə bilərsiniz.</p>
                    <select
                        v-model="form.deck_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                    >
                        <option :value="null">Dəstsiz</option>
                        <option v-for="deck in decks" :key="deck.id" :value="deck.id">
                            {{ deck.title }}
                        </option>
                    </select>
                    <p v-if="form.errors.deck_id" class="text-red-500 text-xs mt-1">{{ form.errors.deck_id }}</p>
                </div>

                <div class="flex gap-3">
                    <button
                        type="button"
                        @click="router.visit(`/stories/${story.id}`)"
                        class="flex-1 py-3 px-4 border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 transition"
                    >Ləğv et</button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex-1 py-3 px-4 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 disabled:opacity-50 transition"
                    >{{ form.processing ? 'Saxlanılır...' : 'Saxla' }}</button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

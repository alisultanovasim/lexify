<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import { ref } from 'vue'

defineProps({ stories: Array })

const deleting = ref(null)

async function deleteStory(id) {
    if (!confirm('Bu hekayəni silmək istəyirsiniz?')) return
    deleting.value = id
    try {
        await axios.delete(`/stories/${id}`)
        router.reload()
    } finally {
        deleting.value = null
    }
}

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
    <AppLayout title="Hekayələr">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Hekayələr</h1>
                    <p class="text-gray-500 text-sm mt-1">{{ stories.length }} hekayə</p>
                </div>
                <button
                    @click="router.visit('/stories/create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 transition"
                >
                    <span class="text-lg leading-none">+</span>
                    Yeni Hekayə
                </button>
            </div>

            <!-- Empty -->
            <div v-if="stories.length === 0" class="text-center py-20">
                <div class="text-6xl mb-4">📖</div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Hekayə yoxdur</h3>
                <p class="text-gray-500 mb-6">Alman mətnini yapışdırıb interaktiv oxumağa başlayın</p>
                <button
                    @click="router.visit('/stories/create')"
                    class="px-6 py-3 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 transition"
                >
                    Hekayə Əlavə Et
                </button>
            </div>

            <!-- Grid -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="story in stories"
                    :key="story.id"
                    class="group bg-white rounded-2xl border border-gray-200 hover:border-indigo-300 hover:shadow-md transition-all p-5 flex flex-col"
                >
                    <!-- Top row -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span
                                v-if="story.level"
                                class="text-xs px-2 py-0.5 rounded-full font-medium"
                                :class="levelColors[story.level] || 'bg-gray-100 text-gray-600'"
                            >{{ story.level }}</span>
                            <span v-if="story.is_read" class="text-xs text-green-600 font-medium">✓ Oxunub</span>
                        </div>
                        <button
                            @click="deleteStory(story.id)"
                            :disabled="deleting === story.id"
                            class="text-gray-300 hover:text-red-500 transition opacity-0 group-hover:opacity-100 text-lg leading-none"
                        >×</button>
                    </div>

                    <!-- Title (clickable) -->
                    <button
                        @click="router.visit(`/stories/${story.id}`)"
                        class="text-left font-semibold text-gray-900 group-hover:text-indigo-600 transition line-clamp-2 mb-2"
                    >{{ story.title }}</button>

                    <!-- Preview -->
                    <p class="text-sm text-gray-400 line-clamp-3 flex-1">{{ story.body }}</p>

                    <!-- Footer -->
                    <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-400">
                        <span v-if="story.deck">📚 {{ story.deck.title }}</span>
                        <span v-else>Dəst yoxdur</span>
                        <button
                            @click="router.visit(`/stories/${story.id}`)"
                            class="text-indigo-600 font-medium hover:underline"
                        >Oxu →</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

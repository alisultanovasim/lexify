<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import { ref } from 'vue'

const props = defineProps({
    stories: Array,
    sort:    { type: String, default: 'desc' },
})

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

function toggleSort() {
    const next = props.sort === 'desc' ? 'asc' : 'desc'
    router.visit(`/stories?sort=${next}`, { preserveScroll: true })
}

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
    <AppLayout title="Hekayələr">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Hekayələr</h1>
                    <p class="text-gray-500 text-sm mt-1">{{ stories.length }} hekayə</p>
                </div>
                <button
                    @click="router.visit('/stories/create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-cyan-600 text-white rounded-xl font-medium hover:bg-cyan-700 transition"
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
                    class="px-6 py-3 bg-cyan-600 text-white rounded-xl font-medium hover:bg-cyan-700 transition"
                >
                    Hekayə Əlavə Et
                </button>
            </div>

            <!-- Table -->
            <div v-else class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50">
                            <!-- Sortable # column -->
                            <th class="w-14 px-4 py-3 text-left">
                                <button
                                    @click="toggleSort"
                                    class="flex items-center gap-1 text-xs font-semibold text-gray-500 uppercase tracking-wide hover:text-cyan-600 transition select-none"
                                    :title="sort === 'desc' ? 'Köhnədən yeniyə sırala' : 'Yenidən köhnəyə sırala'"
                                >
                                    #
                                    <span class="text-base leading-none">
                                        {{ sort === 'desc' ? '↓' : '↑' }}
                                    </span>
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Başlıq</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide hidden sm:table-cell">Səviyyə</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide hidden md:table-cell">Dəst</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide hidden sm:table-cell">Status</th>
                            <th class="w-10 px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr
                            v-for="(story, index) in stories"
                            :key="story.id"
                            class="hover:bg-gray-50 transition group"
                        >
                            <!-- Row number -->
                            <td class="px-4 py-3.5 text-gray-400 font-mono text-xs text-center">
                                {{ index + 1 }}
                            </td>

                            <!-- Title -->
                            <td class="px-4 py-3.5">
                                <button
                                    @click="router.visit(`/stories/${story.id}`)"
                                    class="font-medium text-gray-900 hover:text-cyan-600 transition text-left line-clamp-1"
                                >
                                    {{ story.title }}
                                </button>
                                <p class="text-xs text-gray-400 line-clamp-1 mt-0.5 hidden sm:block">{{ story.body?.slice(0, 80) }}</p>
                            </td>

                            <!-- Level -->
                            <td class="px-4 py-3.5 hidden sm:table-cell">
                                <span
                                    v-if="story.level"
                                    class="text-xs px-2 py-0.5 rounded-full font-medium"
                                    :class="levelColors[story.level] || 'bg-gray-100 text-gray-600'"
                                >{{ story.level }}</span>
                                <span v-else class="text-gray-300">—</span>
                            </td>

                            <!-- Deck -->
                            <td class="px-4 py-3.5 hidden md:table-cell text-xs text-gray-500">
                                <span v-if="story.deck" class="flex items-center gap-1">
                                    <span>📚</span>
                                    <span class="line-clamp-1">{{ story.deck.title }}</span>
                                </span>
                                <span v-else class="text-gray-300">—</span>
                            </td>

                            <!-- Status -->
                            <td class="px-4 py-3.5 hidden sm:table-cell">
                                <span v-if="story.is_read" class="text-xs text-green-600 font-medium">✓ Oxunub</span>
                                <span v-else class="text-xs text-gray-300">—</span>
                            </td>

                            <!-- Delete -->
                            <td class="px-4 py-3.5 text-right">
                                <button
                                    @click="deleteStory(story.id)"
                                    :disabled="deleting === story.id"
                                    class="text-gray-300 hover:text-red-500 transition opacity-0 group-hover:opacity-100 text-lg leading-none"
                                >×</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </AppLayout>
</template>

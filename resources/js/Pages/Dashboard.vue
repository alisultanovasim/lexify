<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { router, usePage } from '@inertiajs/vue3';

defineProps({
  totalDecks: { type: Number, default: 0 },
  totalTerms: { type: Number, default: 0 },
  dueCount:   { type: Number, default: 0 },
  recentDecks: { type: Array, default: () => [] },
});

const { props } = usePage();
</script>

<template>
  <AppLayout title="Ana Səhifə">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

      <!-- Welcome -->
      <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Salam, {{ props.auth.user?.name }}! 👋</h1>
        <p class="text-gray-500 mt-1">Bu gün nə öyrənmək istəyirsiniz?</p>
      </div>

      <!-- Quick stats -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-2xl border border-gray-200 p-4 text-center">
          <p class="text-3xl font-bold text-cyan-600">{{ totalDecks }}</p>
          <p class="text-sm text-gray-500 mt-1">Dəst</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 p-4 text-center">
          <p class="text-3xl font-bold text-cyan-600">{{ totalTerms }}</p>
          <p class="text-sm text-gray-500 mt-1">Söz</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 p-4 text-center">
          <p class="text-3xl font-bold" :class="dueCount > 0 ? 'text-red-500' : 'text-green-600'">{{ dueCount }}</p>
          <p class="text-sm text-gray-500 mt-1">Bu gün təkrar</p>
        </div>
        <button @click="router.visit('/progress')" class="bg-white rounded-2xl border border-gray-200 p-4 text-center hover:border-cyan-300 transition group">
          <p class="text-3xl">📊</p>
          <p class="text-sm text-gray-500 mt-1 group-hover:text-cyan-600">Statistika</p>
        </button>
      </div>

      <!-- Quick actions -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
        <button @click="router.visit('/decks/create')"
          class="flex items-center gap-4 p-5 bg-cyan-600 rounded-2xl text-white hover:bg-cyan-700 transition group">
          <div class="text-3xl">📚</div>
          <div>
            <p class="font-semibold">Yeni Dəst Yarat</p>
            <p class="text-cyan-200 text-sm">Söz dəsti əlavə et</p>
          </div>
        </button>
        <button @click="router.visit('/decks')"
          class="flex items-center gap-4 p-5 bg-white rounded-2xl border border-gray-200 hover:border-cyan-300 transition group">
          <div class="text-3xl">🎯</div>
          <div>
            <p class="font-semibold text-gray-900 group-hover:text-cyan-600">Dəstlərə Bax</p>
            <p class="text-gray-400 text-sm">{{ totalDecks }} dəst mövcuddur</p>
          </div>
        </button>
      </div>

      <!-- Recent decks -->
      <div v-if="recentDecks.length > 0">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-semibold text-gray-900">Son Dəstlər</h2>
          <button @click="router.visit('/decks')" class="text-sm text-cyan-600 hover:underline">Hamısını gör</button>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div v-for="deck in recentDecks" :key="deck.id"
            class="group bg-white rounded-2xl border border-gray-200 hover:border-cyan-300 hover:shadow-sm transition p-4 cursor-pointer"
            @click="router.visit(`/decks/${deck.id}`)">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-sm"
                :style="{ background: deck.color }">
                {{ deck.title[0]?.toUpperCase() }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-medium text-gray-900 truncate group-hover:text-cyan-600 transition">{{ deck.title }}</p>
                <p class="text-xs text-gray-400">{{ deck.terms_count }} söz</p>
              </div>
            </div>
            <div class="flex gap-2">
              <button
                @click.stop="router.visit(`/decks/${deck.id}/study/flashcard`)"
                class="flex-1 py-1.5 text-center text-xs font-medium bg-cyan-50 text-cyan-700 rounded-lg hover:bg-cyan-100 transition">
                🃏 Öyrən
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

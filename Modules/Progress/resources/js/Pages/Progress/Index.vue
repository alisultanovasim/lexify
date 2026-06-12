<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  totalDecks: Number,
  totalTerms: Number,
  studiedTerms: Number,
  dueToday: Array,
  recentSessions: Array,
  deckProgress: Array,
});

const modeLabels = { flashcard:'Kartlar', learn:'Öyrən', write:'Yaz', match:'Uyğunlaş', test:'Test' };
const modeColors = { flashcard:'bg-indigo-100 text-indigo-700', learn:'bg-purple-100 text-purple-700', write:'bg-blue-100 text-blue-700', match:'bg-green-100 text-green-700', test:'bg-orange-100 text-orange-700' };
</script>

<template>
  <AppLayout title="İrəliləyiş">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

      <h1 class="text-2xl font-bold text-gray-900 mb-8">İrəliləyiş</h1>

      <!-- Stats cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
          <p class="text-sm text-gray-500 mb-1">Cəmi dəst</p>
          <p class="text-4xl font-bold text-gray-900">{{ totalDecks }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
          <p class="text-sm text-gray-500 mb-1">Cəmi söz</p>
          <p class="text-4xl font-bold text-gray-900">{{ totalTerms }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
          <p class="text-sm text-gray-500 mb-1">Öyrənilmiş söz</p>
          <p class="text-4xl font-bold text-indigo-600">{{ studiedTerms }}</p>
          <p class="text-xs text-gray-400 mt-1">{{ totalTerms > 0 ? Math.round(studiedTerms/totalTerms*100) : 0 }}% tamamlanıb</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Due today -->
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-900">Bu gün təkrar et</h2>
            <span class="text-sm font-medium px-2.5 py-1 rounded-full"
              :class="dueToday.length > 0 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'">
              {{ dueToday.length }} söz
            </span>
          </div>

          <div v-if="dueToday.length === 0" class="text-center py-8 text-gray-400">
            <div class="text-4xl mb-2">✅</div>
            <p class="text-sm">Bu gün üçün hamısı tamamdır!</p>
          </div>

          <div v-else class="space-y-2 max-h-64 overflow-y-auto">
            <div v-for="item in dueToday" :key="item.term_id"
              class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
              <div>
                <p class="font-medium text-gray-900 text-sm">{{ item.term }}</p>
                <p class="text-xs text-gray-500">{{ item.deck_title }}</p>
              </div>
              <div class="text-right">
                <p class="text-xs text-gray-400">{{ item.repetitions }}x təkrar</p>
              </div>
            </div>
          </div>

          <button v-if="dueToday.length > 0 && deckProgress.length > 0"
            @click="router.visit(`/decks/${deckProgress[0]?.id}/study/learn`)"
            class="mt-4 block w-full py-2.5 text-center bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition">
            Təkrara Başla
          </button>
        </div>

        <!-- Deck progress -->
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
          <h2 class="font-semibold text-gray-900 mb-4">Dəst Proqresi</h2>

          <div v-if="deckProgress.length === 0" class="text-center py-8 text-gray-400">
            <div class="text-4xl mb-2">📚</div>
            <p class="text-sm">Hələ dəst yoxdur</p>
          </div>

          <div v-else class="space-y-3">
            <div v-for="deck in deckProgress" :key="deck.id">
              <div class="flex items-center justify-between mb-1">
                <div class="flex items-center gap-2">
                  <div class="w-3 h-3 rounded-full" :style="{ background: deck.color }"></div>
                  <button @click="router.visit(`/decks/${deck.id}`)" class="text-sm font-medium text-gray-900 hover:text-indigo-600 transition">
                    {{ deck.title }}
                  </button>
                </div>
                <span class="text-xs text-gray-500">{{ deck.studied_count }}/{{ deck.terms_count }}</span>
              </div>
              <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all"
                  :style="{ width: deck.terms_count > 0 ? (deck.studied_count/deck.terms_count*100)+'%' : '0%', background: deck.color }">
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Recent sessions -->
      <div class="mt-6 bg-white rounded-2xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-900 mb-4">Son Sessiyalar</h2>

        <div v-if="recentSessions.length === 0" class="text-center py-8 text-gray-400">
          <div class="text-4xl mb-2">🎮</div>
          <p class="text-sm">Hələ heç bir sessiyanız yoxdur. Öyrənməyə başlayın!</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-gray-500 border-b border-gray-100">
                <th class="pb-3 font-medium">Dəst</th>
                <th class="pb-3 font-medium">Rejim</th>
                <th class="pb-3 font-medium">Nəticə</th>
                <th class="pb-3 font-medium">Vaxt</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="s in recentSessions" :key="s.id" class="hover:bg-gray-50">
                <td class="py-3 font-medium text-gray-900">{{ s.deck_title }}</td>
                <td class="py-3">
                  <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="modeColors[s.mode] || 'bg-gray-100 text-gray-600'">
                    {{ modeLabels[s.mode] || s.mode }}
                  </span>
                </td>
                <td class="py-3">
                  <span class="font-bold" :class="s.score >= 70 ? 'text-green-600' : s.score >= 40 ? 'text-yellow-600' : 'text-red-500'">
                    {{ s.score }}%
                  </span>
                  <span class="text-gray-400 text-xs ml-1">({{ s.correct }}/{{ s.total }})</span>
                </td>
                <td class="py-3 text-gray-400">{{ s.completed_at }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

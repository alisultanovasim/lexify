<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({ deck: Object, languages: Array });

const form = useForm({
  title: props.deck.title,
  description: props.deck.description || '',
  source_language_id: props.deck.source_language_id,
  target_language_id: props.deck.target_language_id,
  color: props.deck.color || '#6366f1',
  is_public: props.deck.is_public,
});

const colors = ['#6366f1','#ec4899','#f59e0b','#10b981','#3b82f6','#8b5cf6','#ef4444','#14b8a6'];
const submit = () => form.put(`/decks/${props.deck.id}`);
</script>

<template>
  <AppLayout title="Dəsti Düzəlt">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex items-center gap-3 mb-8">
        <button @click="router.visit(`/decks/${props.deck.id}`)" class="text-gray-400 hover:text-gray-600">←</button>
        <h1 class="text-2xl font-bold text-gray-900">Dəsti Düzəlt</h1>
      </div>

      <form @submit.prevent="submit" class="bg-white rounded-2xl border border-gray-200 p-6 space-y-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Ad *</label>
          <input v-model="form.title" type="text" required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" />
          <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Açıqlama</label>
          <textarea v-model="form.description" rows="2"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none resize-none"></textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mənbə dil</label>
            <select v-model="form.source_language_id"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
              <option :value="null">Seçin...</option>
              <option v-for="lang in languages" :key="lang.id" :value="lang.id">{{ lang.flag_emoji }} {{ lang.native_name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Hədəf dil</label>
            <select v-model="form.target_language_id"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
              <option :value="null">Seçin...</option>
              <option v-for="lang in languages" :key="lang.id" :value="lang.id">{{ lang.flag_emoji }} {{ lang.native_name }}</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Rəng</label>
          <div class="flex gap-2 flex-wrap">
            <button v-for="c in colors" :key="c" type="button" @click="form.color = c"
              class="w-8 h-8 rounded-full border-2 transition"
              :style="{ background: c }"
              :class="form.color === c ? 'border-gray-800 scale-110' : 'border-transparent'"></button>
          </div>
        </div>

        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
          <div>
            <p class="text-sm font-medium text-gray-900">İctimai dəst</p>
            <p class="text-xs text-gray-500">Digər istifadəçilər görə bilər</p>
          </div>
          <button type="button" @click="form.is_public = !form.is_public"
            class="relative inline-flex h-6 w-11 items-center rounded-full transition"
            :class="form.is_public ? 'bg-indigo-600' : 'bg-gray-200'">
            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition"
              :class="form.is_public ? 'translate-x-6' : 'translate-x-1'"></span>
          </button>
        </div>

        <button type="submit" :disabled="form.processing"
          class="w-full py-3 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 disabled:opacity-50 transition">
          {{ form.processing ? 'Saxlanır...' : 'Dəyişiklikləri Saxla' }}
        </button>
      </form>
    </div>
  </AppLayout>
</template>

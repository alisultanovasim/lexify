<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({ user: Object });

const infoForm = useForm({
  name:  props.user.name,
  email: props.user.email,
});

const passwordForm = useForm({
  current_password: '',
  password:         '',
  password_confirmation: '',
});

const saveInfo = () => infoForm.put('/profile');
const savePassword = () => passwordForm.put('/profile/password', {
  onSuccess: () => passwordForm.reset(),
});
</script>

<template>
  <AppLayout title="Profil">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

      <h1 class="text-2xl font-bold text-gray-900">Profil Ayarları</h1>

      <!-- Info form -->
      <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-900 mb-4">Şəxsi Məlumat</h2>
        <form @submit.prevent="saveInfo" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Ad</label>
            <input v-model="infoForm.name" type="text" required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none" />
            <p v-if="infoForm.errors.name" class="text-red-500 text-xs mt-1">{{ infoForm.errors.name }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">E-poçt</label>
            <input v-model="infoForm.email" type="email" required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none" />
            <p v-if="infoForm.errors.email" class="text-red-500 text-xs mt-1">{{ infoForm.errors.email }}</p>
          </div>
          <button type="submit" :disabled="infoForm.processing"
            class="px-6 py-2.5 bg-cyan-600 text-white rounded-xl font-medium hover:bg-cyan-700 disabled:opacity-50 transition">
            {{ infoForm.processing ? 'Saxlanır...' : 'Saxla' }}
          </button>
        </form>
      </div>

      <!-- Password form -->
      <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-900 mb-4">Şifrəni Dəyiş</h2>
        <form @submit.prevent="savePassword" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mövcud şifrə</label>
            <input v-model="passwordForm.current_password" type="password" required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none" />
            <p v-if="passwordForm.errors.current_password" class="text-red-500 text-xs mt-1">{{ passwordForm.errors.current_password }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Yeni şifrə</label>
            <input v-model="passwordForm.password" type="password" required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none" />
            <p v-if="passwordForm.errors.password" class="text-red-500 text-xs mt-1">{{ passwordForm.errors.password }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Yeni şifrə (təkrar)</label>
            <input v-model="passwordForm.password_confirmation" type="password" required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none" />
          </div>
          <button type="submit" :disabled="passwordForm.processing"
            class="px-6 py-2.5 bg-cyan-600 text-white rounded-xl font-medium hover:bg-cyan-700 disabled:opacity-50 transition">
            {{ passwordForm.processing ? 'Dəyişdirilir...' : 'Şifrəni Dəyiş' }}
          </button>
        </form>
      </div>

    </div>
  </AppLayout>
</template>

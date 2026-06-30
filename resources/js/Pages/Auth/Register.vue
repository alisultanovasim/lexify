<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const name                  = ref('');
const email                 = ref('');
const password              = ref('');
const password_confirmation = ref('');
const processing            = ref(false);
const errors                = ref({});
const generalError          = ref('');
const googleLoading         = ref(false);

const hasErrors = computed(() => Object.keys(errors.value).length > 0 || generalError.value);

const translateError = (field, msg) => {
  if (!msg) return msg;
  const map = {
    'The name field is required.'                     : 'Ad tələb olunur.',
    'The email field is required.'                    : 'E-poçt tələb olunur.',
    'The email has already been taken.'               : 'Bu e-poçt artıq qeydiyyatdan keçib.',
    'The email must be a valid email address.'        : 'E-poçt düzgün formatda deyil.',
    'The password field is required.'                 : 'Şifrə tələb olunur.',
    'The password must be at least 6 characters.'     : 'Şifrə ən az 6 simvol olmalıdır.',
    'The password must be at least 8 characters.'     : 'Şifrə ən az 8 simvol olmalıdır.',
    'The password confirmation does not match.'       : 'Şifrələr uyğun gəlmir.',
    'The password field confirmation does not match.' : 'Şifrələr uyğun gəlmir.',
  };
  return map[msg] || msg;
};

onMounted(() => {
  if (window.google) {
    initGoogle();
  } else {
    const script = document.querySelector('script[src*="accounts.google.com/gsi/client"]');
    if (script) script.addEventListener('load', initGoogle);
  }
});

function initGoogle() {
  window.google.accounts.id.initialize({
    client_id: import.meta.env.VITE_GOOGLE_CLIENT_ID,
    callback:  handleGoogleRegister,
  });

  window.google.accounts.id.renderButton(
    document.getElementById('google-register-btn'),
    { theme: 'outline', size: 'large', width: '100%', locale: 'az' },
  );
}

function handleGoogleRegister(response) {
  googleLoading.value = true;
  router.post('/auth/google', { token: response.credential }, {
    onFinish: () => { googleLoading.value = false; },
  });
}

const submit = async () => {
  errors.value       = {};
  generalError.value = '';
  processing.value   = true;
  try {
    await axios.post('/register', {
      name:                  name.value,
      email:                 email.value,
      password:              password.value,
      password_confirmation: password_confirmation.value,
    });
    window.location.href = '/dashboard';
  } catch (e) {
    if (e.response?.status === 422) {
      const serverErrors = e.response?.data?.errors || {};
      errors.value = Object.fromEntries(
        Object.entries(serverErrors).map(([k, v]) => {
          const msg = Array.isArray(v) ? v[0] : v;
          return [k, translateError(k, msg)];
        }),
      );
    } else if (e.response?.status === 419) {
      generalError.value = 'Sessiya vaxtı bitdi. Səhifəni yeniləyin.';
    } else {
      generalError.value = 'Xəta baş verdi. Yenidən cəhd edin.';
    }
    password.value              = '';
    password_confirmation.value = '';
    processing.value            = false;
  }
};
</script>

<template>
  <GuestLayout title="Qeydiyyat">
    <h2 class="text-xl font-bold text-gray-900 mb-6">Yeni hesab yaradın</h2>

    <!-- General error banner -->
    <div v-if="generalError"
      class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700 flex items-center gap-2">
      <span>⚠️</span>{{ generalError }}
    </div>

    <form @submit.prevent="submit" class="space-y-4">

      <!-- Name -->
      <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Ad</label>
        <input
          id="name"
          v-model="name"
          name="name"
          type="text"
          :class="['w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none',
                   errors.name ? 'border-red-400 bg-red-50' : 'border-gray-300']"
          required autofocus autocomplete="name"
        />
        <p v-if="errors.name" class="text-red-500 text-xs mt-1 flex items-center gap-1">
          <span>✕</span>{{ errors.name }}
        </p>
      </div>

      <!-- Email -->
      <div>
        <label for="reg-email" class="block text-sm font-medium text-gray-700 mb-1">E-poçt</label>
        <input
          id="reg-email"
          v-model="email"
          name="email"
          type="email"
          :class="['w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none',
                   errors.email ? 'border-red-400 bg-red-50' : 'border-gray-300']"
          required autocomplete="email"
        />
        <p v-if="errors.email" class="text-red-500 text-xs mt-1 flex items-center gap-1">
          <span>✕</span>{{ errors.email }}
        </p>
      </div>

      <!-- Password -->
      <div>
        <label for="reg-password" class="block text-sm font-medium text-gray-700 mb-1">
          Şifrə <span class="text-gray-400 font-normal text-xs">(min. 6 simvol)</span>
        </label>
        <input
          id="reg-password"
          v-model="password"
          name="password"
          type="password"
          :class="['w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none',
                   errors.password ? 'border-red-400 bg-red-50' : 'border-gray-300']"
          required autocomplete="new-password"
        />
        <p v-if="errors.password" class="text-red-500 text-xs mt-1 flex items-center gap-1">
          <span>✕</span>{{ errors.password }}
        </p>
      </div>

      <!-- Password confirmation -->
      <div>
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Şifrə (təkrar)</label>
        <input
          id="password_confirmation"
          v-model="password_confirmation"
          name="password_confirmation"
          type="password"
          :class="['w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none',
                   errors.password_confirmation ? 'border-red-400 bg-red-50' : 'border-gray-300']"
          required autocomplete="new-password"
        />
        <p v-if="errors.password_confirmation" class="text-red-500 text-xs mt-1 flex items-center gap-1">
          <span>✕</span>{{ errors.password_confirmation }}
        </p>
      </div>

      <button
        type="submit"
        :disabled="processing"
        class="w-full py-2.5 px-4 bg-cyan-600 text-white rounded-lg font-medium hover:bg-cyan-700 disabled:opacity-50 transition"
      >
        {{ processing ? 'Yaradılır...' : 'Qeydiyyat' }}
      </button>
    </form>

    <!-- Divider -->
    <div class="relative my-5">
      <div class="absolute inset-0 flex items-center">
        <div class="w-full border-t border-gray-200"></div>
      </div>
      <div class="relative flex justify-center text-sm">
        <span class="px-3 bg-white text-gray-400">və ya</span>
      </div>
    </div>

    <!-- Google Register -->
    <div v-if="googleLoading" class="w-full py-2.5 text-center text-sm text-gray-500">
      Google ilə qeydiyyat...
    </div>
    <div v-else id="google-register-btn" class="w-full"></div>

    <p class="text-center text-sm text-gray-500 mt-6">
      Hesabınız var?
      <a href="/login" class="text-cyan-600 hover:underline font-medium">Daxil ol</a>
    </p>
  </GuestLayout>
</template>

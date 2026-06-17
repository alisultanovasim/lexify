<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import { onMounted, computed, ref } from 'vue';

const page = usePage();

const form = useForm({
  email:    '',
  password: '',
  remember: false,
});

const googleLoading = ref(false);

onMounted(() => {
  const savedEmail = localStorage.getItem('login_remember_email');
  if (savedEmail) {
    form.email    = savedEmail;
    form.remember = true;
  }

  if (window.google) {
    initGoogle();
  } else {
    // GSI script hələ yüklənməyibsə - yüklənəndə init et
    const script = document.querySelector('script[src*="accounts.google.com/gsi/client"]');
    if (script) {
      script.addEventListener('load', initGoogle);
    }
  }
});

function initGoogle() {
  window.google.accounts.id.initialize({
    client_id: import.meta.env.VITE_GOOGLE_CLIENT_ID,
    callback: handleGoogleLogin,
  });

  window.google.accounts.id.renderButton(
    document.getElementById('google-signin-btn'),
    { theme: 'outline', size: 'large', width: '100%', locale: 'az' }
  );
}

function handleGoogleLogin(response) {
  googleLoading.value = true;
  router.post('/auth/google', { token: response.credential }, {
    onFinish: () => { googleLoading.value = false; },
  });
}

const submit = () => {
  if (form.remember) {
    localStorage.setItem('login_remember_email', form.email);
  } else {
    localStorage.removeItem('login_remember_email');
  }

  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
};

// Errors from both useForm mechanism and Inertia shared page props
const emailError    = computed(() => form.errors.email    || page.props.errors?.email    || '');
const passwordError = computed(() => form.errors.password || page.props.errors?.password || '');
</script>

<template>
  <GuestLayout title="Giriş">
    <h2 class="text-xl font-bold text-gray-900 mb-6">Hesabınıza daxil olun</h2>

    <form @submit.prevent="submit" class="space-y-4">

      <!-- Email -->
      <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-poçt</label>
        <input
          id="email"
          v-model="form.email"
          name="email"
          type="email"
          :class="['w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none',
                   emailError ? 'border-red-400 bg-red-50' : 'border-gray-300']"
          required autofocus autocomplete="email"
        />
        <p v-if="emailError" class="text-red-500 text-sm mt-1.5 flex items-center gap-1 font-medium">
          <span>✕</span>{{ emailError }}
        </p>
      </div>

      <!-- Password -->
      <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Şifrə</label>
        <input
          id="password"
          v-model="form.password"
          name="password"
          type="password"
          :class="['w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none',
                   passwordError ? 'border-red-400 bg-red-50' : 'border-gray-300']"
          required autocomplete="current-password"
        />
        <p v-if="passwordError" class="text-red-500 text-xs mt-1 flex items-center gap-1">
          <span>✕</span>{{ passwordError }}
        </p>
      </div>

      <!-- Remember + Forgot -->
      <div class="flex items-center justify-between">
        <label for="remember" class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer select-none">
          <input
            id="remember"
            v-model="form.remember"
            name="remember"
            type="checkbox"
            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
          />
          Məni xatırla
        </label>
        <a href="/forgot-password" class="text-sm text-indigo-600 hover:underline">Şifrəni unutdum</a>
      </div>

      <button
        type="submit"
        :disabled="form.processing"
        class="w-full py-2.5 px-4 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 disabled:opacity-50 transition"
      >
        {{ form.processing ? 'Daxil olunur...' : 'Daxil ol' }}
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

    <!-- Google Login -->
    <div v-if="googleLoading" class="w-full py-2.5 text-center text-sm text-gray-500">
      Google ilə daxil olunur...
    </div>
    <div v-else id="google-signin-btn" class="w-full"></div>

    <p class="text-center text-sm text-gray-500 mt-6">
      Hesabınız yoxdur?
      <a href="/register" class="text-indigo-600 hover:underline font-medium">Qeydiyyat</a>
    </p>
  </GuestLayout>
</template>

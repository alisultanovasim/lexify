<script setup>
import { ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import FlashMessage from '@/Components/FlashMessage.vue';

defineProps({ title: String });

const { props } = usePage();
const mobileOpen = ref(false);
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <Head :title="title" />

    <!-- Navbar -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <!-- Logo -->
          <button @click="router.visit('/dashboard')" class="flex items-center gap-2">
            <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
              <span class="text-white font-bold text-sm">L</span>
            </div>
            <span class="font-bold text-gray-900 text-lg">LinguaCards</span>
          </button>

          <!-- Nav links -->
          <div class="hidden md:flex items-center gap-6">
            <button @click="router.visit('/decks')" class="text-gray-600 hover:text-indigo-600 font-medium transition">
              Dəstlər
            </button>
            <button @click="router.visit('/explore')" class="text-gray-600 hover:text-indigo-600 font-medium transition">
              Kəşfet
            </button>
            <button @click="router.visit('/progress')" class="text-gray-600 hover:text-indigo-600 font-medium transition">
              İrəliləyiş
            </button>
          </div>

          <!-- User menu -->
          <div class="flex items-center gap-3">
            <button @click="router.visit('/profile')" class="text-sm text-gray-700 hover:text-indigo-600 transition hidden sm:block">
              {{ props.auth.user?.name }}
            </button>
            <button
              @click="router.post('/logout')"
              class="text-sm text-gray-500 hover:text-red-600 transition"
            >Çıxış</button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Flash messages -->
    <FlashMessage />

    <!-- Page Content -->
    <main>
      <slot />
    </main>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="text-center max-w-md">
      <div class="text-8xl font-black text-red-500 mb-4">500</div>
      <h1 class="text-2xl font-bold text-gray-900 mb-2">Server xətası</h1>
      <p class="text-gray-500 mb-8">Bir şeylər səhv getdi. Bir az sonra yenidən cəhd edin.</p>
      <Link href="/" class="px-6 py-3 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 transition">
        Ana Səhifəyə Qayıt
      </Link>
    </div>
  </div>
</template>

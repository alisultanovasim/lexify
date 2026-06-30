<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ status: Number });
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="text-center max-w-md">
      <div class="text-8xl font-black text-cyan-600 mb-4">404</div>
      <h1 class="text-2xl font-bold text-gray-900 mb-2">Səhifə tapılmadı</h1>
      <p class="text-gray-500 mb-8">Axtardığınız səhifə mövcud deyil və ya silinib.</p>
      <Link href="/" class="px-6 py-3 bg-cyan-600 text-white rounded-xl font-medium hover:bg-cyan-700 transition">
        Ana Səhifəyə Qayıt
      </Link>
    </div>
  </div>
</template>

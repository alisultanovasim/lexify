<script setup>
defineProps({ title: String });
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-cyan-50 flex items-center justify-center p-4">
    <Head :title="title" />
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-cyan-500 rounded-2xl mb-4">
          <span class="text-white font-bold text-2xl">L</span>
        </div>
        <h1 class="text-3xl font-bold text-gray-900">LinguaCards</h1>
        <p class="text-gray-500 mt-1">Dil öyrənməyi asanlaşdır</p>
      </div>
      <div class="bg-white rounded-2xl shadow-lg p-8">
        <slot />
      </div>
    </div>
  </div>
</template>

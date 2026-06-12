<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const visible = ref(false);
const message = ref('');
const type = ref('success');

watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.success) {
      message.value = flash.success;
      type.value = 'success';
      visible.value = true;
      setTimeout(() => { visible.value = false; }, 4000);
    } else if (flash?.error) {
      message.value = flash.error;
      type.value = 'error';
      visible.value = true;
      setTimeout(() => { visible.value = false; }, 5000);
    }
  },
  { deep: true }
);
</script>

<template>
  <Transition
    enter-active-class="transition ease-out duration-300"
    enter-from-class="opacity-0 translate-y-[-1rem]"
    enter-to-class="opacity-100 translate-y-0"
    leave-active-class="transition ease-in duration-200"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="visible"
      class="fixed top-20 right-4 z-50 max-w-sm px-4 py-3 rounded-xl shadow-lg text-white text-sm font-medium flex items-center gap-2"
      :class="type === 'success' ? 'bg-green-500' : 'bg-red-500'"
    >
      <span>{{ type === 'success' ? '✓' : '✕' }}</span>
      {{ message }}
    </div>
  </Transition>
</template>

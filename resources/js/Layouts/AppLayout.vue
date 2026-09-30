<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import FlashMessage from '@/Components/FlashMessage.vue';
import UniversalStudyModal from '@/Components/UniversalStudyModal.vue';
import { useDarkMode } from '@/composables/useDarkMode.js';

defineProps({ title: String });

const page = usePage();
const user = computed(() => page.props.auth?.user);
const url  = computed(() => page.url);

const active = (path) => url.value?.startsWith(path);
const { dark, toggle } = useDarkMode();

const universalOpen = ref(false);
const modalMode     = ref(null);

const openModal = (mode) => {
  modalMode.value     = mode;
  universalOpen.value = false;
};

const closeModal = () => { modalMode.value = null; };
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex">
    <Head :title="title" />

    <!-- ── Sidebar ──────────────────────────────────────────────────────── -->
    <aside class="w-60 bg-white border-r border-slate-200 flex flex-col fixed inset-y-0 left-0 z-40">

      <!-- Logo -->
        <a href="/dashboard">
            <div class="flex items-center gap-3 px-4 py-5 border-b border-slate-100">
                <img src="/logo.jpg" alt="Logo" class="w-8 h-8 object-contain rounded-lg flex-shrink-0" />
                <span class="font-bold text-slate-900 text-base">Lexify</span>
            </div>
        </a>

      <!-- Nav links -->
      <nav class="flex-1 px-3 py-4 space-y-0.5">
        <button
          @click="router.visit('/decks')"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition"
          :class="active('/decks') ? 'bg-cyan-50 text-cyan-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
        >
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
          </svg>
          Dəstlər
        </button>

        <button
          @click="router.visit('/explore')"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition"
          :class="active('/explore') ? 'bg-cyan-50 text-cyan-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
        >
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          Kəşfet
        </button>

        <button
          @click="router.visit('/progress')"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition"
          :class="active('/progress') ? 'bg-cyan-50 text-cyan-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
        >
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
          </svg>
          İrəliləyiş
        </button>

        <button
          @click="router.visit('/stories')"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition"
          :class="active('/stories') ? 'bg-cyan-50 text-cyan-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
        >
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
          </svg>
          Hekayələr
        </button>

        <!-- ── Ümumi sinaq (accordion dropdown) ──────────────────────────── -->
        <div>
          <button
            @click="universalOpen = !universalOpen"
            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition"
            :class="universalOpen
              ? 'bg-cyan-50 text-cyan-700'
              : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            <span class="flex-1 text-left">Ümumi sinaq</span>
            <svg
              class="w-4 h-4 flex-shrink-0 transition-transform duration-200"
              :class="universalOpen ? 'rotate-180' : ''"
              fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>

          <!-- Dropdown items -->
          <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1"
          >
            <div v-if="universalOpen" class="mt-1 ml-8 space-y-0.5">
              <button
                @click="openModal('learn')"
                class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-medium transition text-slate-600 hover:bg-cyan-50 hover:text-cyan-700"
              >
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                Öyrən
              </button>
              <button
                @click="openModal('test')"
                class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-medium transition text-slate-600 hover:bg-cyan-50 hover:text-cyan-700"
              >
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Test
              </button>
              <button
                @click="openModal('match')"
                class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-medium transition text-slate-600 hover:bg-cyan-50 hover:text-cyan-700"
              >
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                Uyğunlaş
              </button>
            </div>
          </Transition>
        </div>

      </nav>

      <!-- Dark mode toggle -->
      <div class="px-3 pb-2">
        <button
          @click="toggle"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition text-slate-500 hover:bg-slate-50 hover:text-slate-700"
        >
          <!-- Sun -->
          <svg v-if="dark" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
          </svg>
          <!-- Moon -->
          <svg v-else class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
          </svg>
          {{ dark ? 'İşıqlı rejim' : 'Qaranlıq rejim' }}
        </button>
      </div>

      <!-- User section -->
      <div class="px-3 py-4 border-t border-slate-100 space-y-0.5">
        <button
          @click="router.visit('/profile')"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition text-slate-600 hover:bg-slate-50 hover:text-slate-900"
        >
          <div class="w-7 h-7 bg-cyan-100 rounded-full flex items-center justify-center text-cyan-700 font-semibold text-xs flex-shrink-0">
            {{ user?.name?.charAt(0)?.toUpperCase() }}
          </div>
          <span class="truncate">{{ user?.name }}</span>
        </button>
        <button
          @click="router.post('/logout')"
          class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium transition text-slate-400 hover:bg-red-50 hover:text-red-500"
        >
          <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
          </svg>
          Çıxış
        </button>
      </div>
    </aside>

    <!-- ── Main content ─────────────────────────────────────────────────── -->
    <main class="flex-1 ml-60 min-w-0">
      <FlashMessage />
      <slot />
    </main>

    <!-- ── Universal Study Modal (root level — above all z-indexes) ──── -->
    <UniversalStudyModal
      v-if="modalMode"
      :mode="modalMode"
      @close="closeModal"
    />
  </div>
</template>

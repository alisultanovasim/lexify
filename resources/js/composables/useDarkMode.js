import { ref, watchEffect } from 'vue';

const dark = ref(
    typeof window !== 'undefined' ? localStorage.getItem('theme') === 'dark' : false
);

if (typeof window !== 'undefined') {
    watchEffect(() => {
        document.documentElement.classList.toggle('dark', dark.value);
        localStorage.setItem('theme', dark.value ? 'dark' : 'light');
    });
}

export function useDarkMode() {
    return {
        dark,
        toggle: () => { dark.value = !dark.value; },
    };
}

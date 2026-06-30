<script setup>
import { ref } from 'vue';

const props = defineProps({
  text: { type: String, required: true },
  lang: { type: String, default: 'en' },
  size: { type: String, default: 'md' }, // sm | md | lg
});

const speaking = ref(false);
const unsupported = ref(!window.speechSynthesis);

// BCP-47 language tags for Web Speech API
const langMap = {
  // Sistemdə mövcud dillər
  de: 'de-DE',   // Alman
  en: 'en-US',   // İngilis
  ru: 'ru-RU',   // Rus
  tr: 'tr-TR',   // Türk
  az: 'tr-TR',   // Azərbaycan → Türk (ən yaxın mövcud səs)
  // Geniş dəstək
  zh: 'zh-CN',   // Çin (sadələşdirilmiş)
  ja: 'ja-JP',   // Yapon
  ko: 'ko-KR',   // Koreya
  fr: 'fr-FR',   // Fransız
  es: 'es-ES',   // İspan
  it: 'it-IT',   // İtalyan
  pt: 'pt-PT',   // Portuqal
  pl: 'pl-PL',   // Polyak
  nl: 'nl-NL',   // Hollandiya
  sv: 'sv-SE',   // İsveç
  no: 'nb-NO',   // Norveç
  ar: 'ar-SA',   // Ərəb
  hi: 'hi-IN',   // Hindi
  uk: 'uk-UA',   // Ukrayna
};

// Smart voice selector: tries to find the best available voice for a language
const getBestVoice = (bcp47) => {
  const voices = window.speechSynthesis.getVoices();
  if (!voices.length) return null;

  const langPrefix = bcp47.split('-')[0]; // e.g. 'zh' from 'zh-CN'

  // Priority 1: exact match
  let voice = voices.find(v => v.lang === bcp47);
  if (voice) return voice;

  // Priority 2: same language, any region
  voice = voices.find(v => v.lang.startsWith(langPrefix + '-'));
  if (voice) return voice;

  // Priority 3: same language prefix (loose match)
  voice = voices.find(v => v.lang.startsWith(langPrefix));
  return voice || null;
};

const speak = () => {
  if (!window.speechSynthesis || !props.text) return;

  window.speechSynthesis.cancel();

  const bcp47     = langMap[props.lang] || 'en-US';
  const utterance = new SpeechSynthesisUtterance(props.text);
  utterance.lang  = bcp47;
  utterance.rate  = 0.85;
  utterance.pitch = 1;

  // Try to assign the best available voice
  // (voices load async; if not ready yet, browser uses default for the lang)
  const voices = window.speechSynthesis.getVoices();
  if (voices.length) {
    const best = getBestVoice(bcp47);
    if (best) utterance.voice = best;
  }

  utterance.onstart = () => { speaking.value = true; };
  utterance.onend   = () => { speaking.value = false; };
  utterance.onerror = () => { speaking.value = false; };

  window.speechSynthesis.speak(utterance);
};
</script>

<template>
  <button
    v-if="!unsupported"
    type="button"
    @click.stop="speak"
    :title="`'${text}' sözünü dinlə`"
    :class="[
      'rounded-full transition flex items-center justify-center flex-shrink-0',
      speaking
        ? 'text-cyan-600 bg-cyan-50'
        : 'text-gray-400 hover:text-cyan-500 hover:bg-cyan-50',
      size === 'sm' ? 'w-6 h-6'  : '',
      size === 'md' ? 'w-8 h-8'  : '',
      size === 'lg' ? 'w-10 h-10' : '',
    ]"
  >
    <svg v-if="!speaking" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
      :class="size === 'sm' ? 'w-3.5 h-3.5' : size === 'lg' ? 'w-5 h-5' : 'w-4 h-4'">
      <path d="M13.5 4.06c0-1.336-1.616-2.005-2.56-1.06l-4.5 4.5H4.508c-1.141 0-2.318.664-2.66 1.905A9.76 9.76 0 0 0 1.5 12c0 .898.121 1.768.35 2.595.341 1.24 1.518 1.905 2.659 1.905h1.93l4.5 4.5c.945.945 2.561.276 2.561-1.06V4.06ZM18.584 5.106a.75.75 0 0 1 1.06 0c3.808 3.807 3.808 9.98 0 13.788a.75.75 0 0 1-1.06-1.06 8.25 8.25 0 0 0 0-11.668.75.75 0 0 1 0-1.06Z" />
      <path d="M15.932 7.757a.75.75 0 0 1 1.061 0 6 6 0 0 1 0 8.486.75.75 0 0 1-1.06-1.061 4.5 4.5 0 0 0 0-6.364.75.75 0 0 1 0-1.06Z" />
    </svg>
    <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
      :class="['animate-pulse', size === 'sm' ? 'w-3.5 h-3.5' : size === 'lg' ? 'w-5 h-5' : 'w-4 h-4']">
      <path d="M13.5 4.06c0-1.336-1.616-2.005-2.56-1.06l-4.5 4.5H4.508c-1.141 0-2.318.664-2.66 1.905A9.76 9.76 0 0 0 1.5 12c0 .898.121 1.768.35 2.595.341 1.24 1.518 1.905 2.659 1.905h1.93l4.5 4.5c.945.945 2.561.276 2.561-1.06V4.06ZM18.584 5.106a.75.75 0 0 1 1.06 0c3.808 3.807 3.808 9.98 0 13.788a.75.75 0 0 1-1.06-1.06 8.25 8.25 0 0 0 0-11.668.75.75 0 0 1 0-1.06Z" />
      <path d="M15.932 7.757a.75.75 0 0 1 1.061 0 6 6 0 0 1 0 8.486.75.75 0 0 1-1.06-1.061 4.5 4.5 0 0 0 0-6.364.75.75 0 0 1 0-1.06Z" />
    </svg>
  </button>
</template>

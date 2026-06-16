# LinguaCards — Layihə Kontekst Faylı

> **İstifadə qaydası**: Hər yeni chat-da ilk mesajınızda yazın:
> `"PROJECT_CONTEXT.md faylını oxu, sonra tapşırığı ver"`

---

## 1. Layihə Haqqında

**Ad**: LinguaCards — Quizlet alternativi (şəxsi istifadə üçün)
**Məqsəd**: Dil öyrənmə — əsasən Alman dili (DE→AZ)
**URL**: `http://127.0.0.1:7000` (`php artisan serve --port=7000`)
**Qovluq**: `C:\laragon\www\quizlanguage`

---

## 2. Tech Stack

| Qat | Texnologiya |
|-----|-------------|
| Backend | Laravel 11, PHP 8.3 |
| Frontend | Vue 3 (Composition API), Inertia.js v3 |
| CSS | Tailwind CSS v4 |
| DB | MySQL (`quizlanguage` database) |
| Modullar | nwidart/laravel-modules v13 |
| Build | Vite 8 |
| AI | OpenAI gpt-4o-mini → Deepseek → Gemini (fallback) |
| Şəkil | Pixabay API |

---

## 3. Modul Strukturu

```
Modules/
├── Auth/          — Service provider (routes routes/web.php ilə idarə olunur)
├── Deck/          — Dəst CRUD, DeckPolicy, TermCard, ImagePicker
├── Vocabulary/    — Term CRUD, TermController, AI Enrichment Service
├── Study/         — 5 öyrənmə rejimi (Flashcard, Learn, Write, Match, Test)
├── Image/         — Pixabay image search, ImageStorageService
├── Progress/      — Spaced Repetition (SM-2), statistika
├── Language/      — 5 dil (az, de, en, ru, tr) — seeder ilə
└── Import/        — Toplu import (CSV/semicolon format)
```

---

## 4. Əsas Fayllar

### Backend
| Fayl | Məqsəd |
|------|--------|
| `app/Http/Controllers/Auth/LoginController.php` | axios POST → JSON 422 qaytarır (redirect yox) |
| `app/Http/Controllers/Auth/RegisterController.php` | min:6 şifrə, JSON 422 xəta |
| `Modules/Vocabulary/app/Services/GeminiEnrichmentService.php` | Multi-provider AI: OpenAI→Deepseek→Gemini |
| `Modules/Image/app/Services/GoogleImageService.php` | Pixabay image search + OpenAI translate |
| `Modules/Image/app/Http/Controllers/ImageController.php` | Image search + save endpoint |
| `Modules/Vocabulary/app/Http/Controllers/TermController.php` | Term CRUD + enrich + lastTerm |
| `Modules/Deck/app/Http/Controllers/DeckController.php` | Deck CRUD + explore + clone |
| `KNOWN_BUGS.md` | Kritik buglar və qaydalar — HƏR DƏYİŞİKLİKDƏN ƏVVƏL OXU! |

### Frontend
| Fayl | Məqsəd |
|------|--------|
| `resources/js/app.js` | Inertia setup, ZiggyVue, axios config |
| `resources/js/Layouts/AppLayout.vue` | Navbar (button+router.visit, NOT Link) |
| `resources/js/Pages/Auth/Login.vue` | useForm + localStorage email, Inertia errors |
| `resources/js/Pages/Auth/Register.vue` | axios POST + window.location.href |
| `Modules/Deck/resources/js/Pages/Decks/Show.vue` | Infinite scroll, TermCard list |
| `Modules/Deck/resources/js/Components/TermCard.vue` | Edit modal, AI re-enrich, ImagePicker |
| `Modules/Deck/resources/js/Components/ImagePicker.vue` | Pixabay image search+save |
| `Modules/Vocabulary/resources/js/Pages/Terms/Create.vue` | Söz əlavə et + AI + image suggest |
| `resources/js/Components/SpeakButton.vue` | Browser TTS (window.speechSynthesis) |

---

## 5. API Açarları (.env)

```env
OPENAI_API_KEY=sk-proj-...        # GPT-4o-mini, ödənişli
DEEPSEEK_API_KEY=sk-...           # deepseek-chat, ödənişli (ucuz)
GEMINI_API_KEY=AQ.Ab8RN6...       # Hal-hazırda limit:0 (işləmir)
PIXABAY_API_KEY=56249932-...      # Şəkil axtarışı, pulsuz
GOOGLE_IMAGE_API_KEY=AIzaSy...    # Custom Search, 403 problemi var (Pixabay istifadə olunur)
GOOGLE_SEARCH_ENGINE_ID=f49...    # Custom Search Engine ID
```

**AI Fallback sırası**: OpenAI → Deepseek → Gemini

---

## 6. Veritabanı

**Connection**: MySQL, DB=`quizlanguage`

| Cədvəl | Məqsəd |
|--------|--------|
| users | İstifadəçilər |
| decks | Söz dəstləri |
| terms | Sözlər |
| term_examples | Nümunə cümlələr |
| term_images | Şəkillər (local storage) |
| languages | 5 dil (az,de,en,ru,tr) |
| study_sessions | Öyrənmə sessiyaları |
| study_answers | Sessiya cavabları |
| user_term_progress | Spaced repetition (SM-2) |

**Default user** (seeder):
- Email: `asim@gmail.com`
- Password: `123456`

**Fresh seed**: `php artisan migrate:fresh --seed`

---

## 7. Kritik Qaydalar (KNOWN_BUGS.md-dən)

### 🚨 Navigation: `<Link>` yox, `<button @click="router.visit()">` OLSUN
```js
// YANLIŞ:
<Link :href="`/decks/${id}`">

// DÜZGÜN:
<button @click="router.visit(`/decks/${id}`)">
```
**Səbəb**: Inertia v3.3.1-də `<Link>` href computed property bug var — toString xətası atır, navigation işləmir.

### 🚨 lastTerm/enrichLast: `deck->terms()->latest()` ETMƏ
```php
// YANLIŞ (position orderBy override edir, yanlış term qaytarır):
$deck->terms()->latest()->first()

// DÜZGÜN:
Term::where('deck_id', $deck->id)->orderByDesc('id')->first()
```

### 🚨 Image save: `webformatURL` deyil, CDN URL istifadə et
Pixabay `webformatURL` server-side download üçün işləmir (expires, auth tələb edir).
`previewURL`-də `_150` → `_640` əvəz edir → CDN 640px URL istifadə edir.

### ⚠️ ZiggyVue + @routes SAXLA
`app.js`-dən ZiggyVue-nu silmə, `app.blade.php`-dən `@routes`-u silmə — app işləməz.

### ⚠️ app.config.errorHandler ƏLAVƏ ETMƏ
Vue errorHandler əlavə etmək navigation-ı bloklayır.

### ⚠️ router.visit try-catch ƏLAVƏ ETMƏ
try-catch navigation-ı yutaraq bloklayır.

---

## 8. Şəkil Saxlama

- **Yol**: `storage/app/public/term_images/{term_id}/{uuid}.jpg`
- **URL**: `http://127.0.0.1:7000/storage/term_images/{term_id}/{uuid}.jpg`
- **Symlink**: `public/storage → storage/app/public` (`php artisan storage:link`)

---

## 9. AI Enrichment (Söz Zənginləşdirməsi)

**Servis**: `GeminiEnrichmentService.php`
**Nəticə**: pronunciation, gender (der/die/das), plural_form, part_of_speech, examples
**Prompt dili**: İngilis (OpenAI-compatible JSON format)
**Fallback**: OpenAI → Deepseek → Gemini

---

## 10. Öyrənmə Rejimləri

| Rejim | Status | Fayl |
|-------|--------|------|
| Flashcard | ✅ | FlashcardMode.vue |
| Learn (adaptiv) | ✅ | LearnMode.vue |
| Write | ✅ | WriteMode.vue |
| Match | ✅ | MatchMode.vue |
| Test (multiple choice) | ✅ | TestMode.vue |

**Fuzzy matching**: Levenshtein distance (≤3 typo qəbul edilir), çoxlu tərcümə (vergüllə ayrılmış) qəbul edilir.

---

## 11. TTS (Səsləndirmə)

`resources/js/Components/SpeakButton.vue`
- Browser native `window.speechSynthesis` — pulsuz, API yoxdur
- Yalnız **mənbə dil** (söz) səsləndirilir — tərcümə yox
- `de→de-DE`, `ru→ru-RU`, `zh→zh-CN`, `az→tr-TR` (fallback)

---

## 12. Başlatma Əmrləri

```bash
# Laragon → MySQL başlat
php artisan serve --port=7000    # PHP server
npm run dev                       # Vite dev server (ayrı terminal)

# Production build
npm run build
php artisan optimize

# DB sıfırla
php artisan migrate:fresh --seed

# Cache sil
php artisan optimize:clear
```

---

## 13. Import Formatı

**Nöqtəli vergül modu** (tövsiyə):
```
die Bedeutung,əhəmiyyət;verbessern,yaxşılaşdırmaq;das Haus,ev
```
**Sətir-sətir modu** (tab/vergül/nöqtəli vergül ilə):
```
Haus	ev
Baum	ağac
```

---

## 14. Show.vue Xüsusiyyətləri

`Modules/Deck/resources/js/Pages/Decks/Show.vue` — **infinite scroll** ilə işləyir:
- Terms `GET /decks/{id}/terms?page=N&search=...` endpoint-indən yüklənir
- TermCard `@updated` və `@deleted` event-lər emitlənir
- Show.vue **terms prop-u yoxdur** (DeckController-dən gəlmir) — axios ilə lazy load edilir

---

## 15. Login Xüsusiyyətləri

`Login.vue` — Inertia `useForm` istifadə edir (axios deyil):
- `localStorage`-da email saxlanır ("Məni xatırla" üçün)
- Xəta göstərmə: `form.errors` + `page.props.errors`
- `LoginController` JSON 422 qaytarır (AJAX üçün)

`Register.vue` — `axios.post` + `window.location.href` istifadə edir (Inertia form yox).

---

*Bu fayl avtomatik yenilənir. Son yeniləmə: 2026-06-12*

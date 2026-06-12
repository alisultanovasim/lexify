# LinguaCards — Bilinen Problemlər və Qaydalar

Bu faylı hər hansı düzəliş etməzdən əvvəl oxu.

---

## 🚨 KRİTİK: Yanlış term-ə şəkil/enrichment yazılır

**Problem**: Yeni söz əlavə edildikdən sonra şəkil və ya AI enrichment BAŞQA söze yazılır.

**Kök səbəb**: `Deck::terms()` relasiyasında `->orderBy('position')` var. `$deck->terms()->latest()->first()` çağrısında `latest()` override ETMİR, iki ORDER BY yaranır — position ASC üstünlük alır, beləliklə həmişə ən aşağı position-lu (ilk əlavə edilmiş) söz qaytarılır.

**HƏLLİ (aktiv)**:
```php
// YANLIŞ:
$deck->terms()->latest()->first()

// DÜZGÜN:
Term::where('deck_id', $deck->id)->orderByDesc('id')->first()
```

**Tətbiq edildiyi yerlər**: `TermController::enrichLast`, `TermController::lastTerm`

**ETMƏ**: Hərgiz `$deck->terms()->latest()` istifadə etmə. Həmişə `Term::where('deck_id', ...)` istifadə et.

---

## 🚨 KRİTİK: Düymələr işləmir (Navigation broken)

**Problem**: Bəzi dəyişikliklər düymələrin click etdikdə işləməməsinə səbəb olur.

**Simptom**: Düymə click edilir, network request görünür, amma səhifə dəyişmir.

**Kök səbəb**: Inertia v3.3.1-in `<Link>` komponenti `href` computed property-sini evaluate edərkən bəzən `undefined.toString()` xətası atır.

**HƏLLİ (aktiv)**:
- Bütün navigasiya `<Link>` komponentləri `<button @click="router.visit(url)">` ilə əvəz edilib
- `app.js`-də ZiggyVue saxlanılır (silmək problemi yenidən yaradır)
- `@routes` blade directive saxlanılır (ZiggyVue üçün lazımdır)
- `vite.config.js`-də `@modules` alias saxlanılır

**ETMƏ**:
- `app.js`-dən ZiggyVue-nu silmə
- `app.blade.php`-dən `@routes`-u silmə
- `app.config.errorHandler` əlavə etmə (funksionallığı bloklayır)
- `router.visit` üzərindən try-catch wrapper əlavə etmə (navigation-ı yutaraq bloklayır)

---

## 🚨 KRİTİK: Hamı düyməsi blur olur / seçilə bilmir

**Problem**: Şəkil seçəndə `imgSaving=true` bütün şəkilləri `disabled:opacity-50` edir.

**HƏLLİ (aktiv)**:
- `ImagePicker.vue`: `savingIdx` ref-i istifadə et — yalnız seçilən index spinner göstərir
- `Create.vue`: `savingIndex` ref-i istifadə et — eyni məntiqlə

**ETMƏ**:
- `saving = ref(false)` ilə bütün şəkilləri disable etmə
- `:disabled="saving"` istifadə etmə image grid-ə

---

## ⚠️ Restart sonrası işləmir

**Problem**: Kompüter restart edildikdən sonra düymələr işləmir.

**Səbəb**: Brauzer köhnə cache-lənmiş JS yükləyir.

**HƏLLİ**:
- Restart sonrası brauzerdə **Ctrl+Shift+R** (hard refresh) et
- `HandleInertiaRequests::version()` `null` qaytarır (aktual) — versiya yoxlaması deaktivdir
- Başlatma ardıcıllığı: 1) Laragon (MySQL), 2) `npm run dev`, 3) `php artisan serve --port=7000`, 4) Brauzer → Ctrl+Shift+R

---

## ℹ️ Şəkil save edildi amma görünmür

**Problem**: Söz əlavə edəndə şəkil seçilir amma TermCard-da görünmür.

**Səbəb**: Şəkil DB-a saxlanılır (storage/app/public/term_images/), amma Create.vue səhifəsi deck görünüşünü yeniləmir.

**HƏLLİ (aktiv)**:
- Şəkil seçildikdən sonra `✓ Şəkil əlavə edildi` + thumbnail preview göstərilir
- "Dəstə qayıdanda görünəcək" mesajı
- Dəstə qayıtdıqda (← düyməsi) şəkil avtomatik yüklənir

---

## ℹ️ toString() konsol xətası

**Problem**: `Cannot read properties of undefined (reading 'toString')` konsol-da görünür.

**Səbəb**: Inertia v3.3.1 `<Link>` komponenti bug-ı. Funksionallığı pozmur.

**HƏLLİ (aktiv)**:
- `window.addEventListener('unhandledrejection', ...)` ilə yalnız bu xəta suppress edilir
- Düymələr `<button @click="router.visit()">` ilə əvəz edilib
- ETMƏ: Bu suppressor-u silmə

---

## ℹ️ API Açarları

| Dəyişən | Məqsəd | Format |
|---------|--------|--------|
| `OPENAI_API_KEY` | AI söz zənginləşdirməsi | `sk-proj-...` |
| `PIXABAY_API_KEY` | Şəkil axtarışı | `12345678-abc...` |
| `GEMINI_API_KEY` | Artıq istifadə edilmir | — |
| `GOOGLE_IMAGE_API_KEY` | Artıq istifadə edilmir | — |

---

## ℹ️ Vacib Texniki Məlumatlar

- `router.visit()` istifadəsi: `<Link>` ETMƏ, `<button @click="router.visit()">` OLSUN
- `app.js`: ZiggyVue + `@routes` mütləq saxlanmalıdır
- `HandleInertiaRequests::version()` `null` qaytarır (local env üçün)
- Şəkillər: `storage/app/public/term_images/{term_id}/{uuid}.ext`
- TTS: Browser native `window.speechSynthesis`, heç bir API çağırışı yoxdur
- Pronunciation (`/haʊs/`): `terms.pronunciation` sütununda saxlanılır (AI tərəfindən)

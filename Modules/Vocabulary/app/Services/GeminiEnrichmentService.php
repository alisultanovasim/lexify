<?php
namespace Modules\Vocabulary\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Vocabulary\Models\Term;

/**
 * Multi-provider AI enrichment service.
 * Tries providers in order: OpenAI → Deepseek → Gemini
 * Falls back to next provider on quota/error.
 */
class GeminiEnrichmentService
{
    // Provider chain: tried in this order — 1) OpenAI  2) Deepseek  3) Gemini
    private array $providers = [
        [
            'name'    => 'openai',
            'key'     => '',
            'model'   => 'gpt-4o-mini',
            'type'    => 'openai',
            'url'     => 'https://api.openai.com/v1/chat/completions',
        ],
        [
            'name'    => 'deepseek',
            'key'     => '',
            'model'   => 'deepseek-chat',
            'type'    => 'openai',
            'url'     => 'https://api.deepseek.com/v1/chat/completions',
        ],
        [
            'name'    => 'gemini',
            'key'     => '',
            'model'   => 'gemini-2.0-flash',
            'type'    => 'gemini',
            'url'     => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent',
        ],
    ];

    public function __construct()
    {
        $this->providers[0]['key'] = config('vocabulary.openai_api_key', '');
        $this->providers[1]['key'] = config('vocabulary.deepseek_api_key', '');
        $this->providers[2]['key'] = config('vocabulary.gemini_api_key', '');
    }

    // Returns: 'ok' | 'no_key' | 'quota' | 'error'
    public function enrich(Term $term, ?string $sourceLang = null, ?string $targetLang = null): string
    {
        $prompt = $this->buildPrompt($term->term, $term->definition, $sourceLang, $targetLang);

        foreach ($this->providers as $provider) {
            if (empty($provider['key'])) {
                Log::debug("AI provider [{$provider['name']}] skipped: no key");
                continue;
            }

            $result = $provider['type'] === 'gemini'
                ? $this->callGemini($provider, $prompt)
                : $this->callOpenAICompatible($provider, $prompt);

            if ($result['status'] === 'ok') {
                $this->applyEnrichment($term, $result['data']);
                Log::info("AI enrichment ok", ['provider' => $provider['name'], 'term_id' => $term->id]);
                return 'ok';
            }

            // quota or not_found → try next provider
            if (in_array($result['status'], ['quota', 'not_found', 'expired'])) {
                Log::info("AI provider [{$provider['name']}] skip", ['reason' => $result['status']]);
                continue;
            }

            // no_key → skip this provider
            if ($result['status'] === 'no_key') {
                continue;
            }

            // hard error → still try next (resilient)
            Log::warning("AI provider [{$provider['name']}] error", ['status' => $result['status']]);
        }

        Log::warning('AI enrichment: all providers failed', ['term_id' => $term->id]);
        return 'quota'; // most common reason when all fail
    }

    // ── OpenAI-compatible call (OpenAI + Deepseek) ──────────────────────────
    private function callOpenAICompatible(array $provider, string $prompt): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $provider['key'],
                'Content-Type'  => 'application/json',
            ])->timeout(20)->post($provider['url'], [
                'model'       => $provider['model'],
                'messages'    => [
                    ['role' => 'system', 'content' => 'You are a language learning assistant. Always respond with valid JSON only, no markdown.'],
                    ['role' => 'user',   'content' => $prompt],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.1,
                'max_tokens'  => 400,
            ]);

            if (!$response->successful()) {
                $status = $response->status();
                $msg    = $response->json('error.message') ?? '';
                Log::debug("Provider [{$provider['name']}] HTTP $status: $msg");

                if ($status === 429)                     return ['status' => 'quota'];
                if ($status === 402)                     return ['status' => 'quota']; // Insufficient Balance
                if ($status === 401 || $status === 403)  return ['status' => 'no_key'];
                if ($status === 400 && (str_contains($msg, 'expired') || str_contains($msg, 'Balance') || str_contains($msg, 'balance'))) return ['status' => 'quota'];
                return ['status' => 'error'];
            }

            $content = $response->json('choices.0.message.content') ?? '';
            return $this->parseJsonContent($content);

        } catch (\Exception $e) {
            Log::error("Provider [{$provider['name']}] exception: " . $e->getMessage());
            return ['status' => 'error'];
        }
    }

    // ── Gemini call ──────────────────────────────────────────────────────────
    private function callGemini(array $provider, string $prompt): array
    {
        try {
            $url = $provider['url'] . '?key=' . $provider['key'];

            $response = Http::timeout(20)->post($url, [
                'contents'         => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 0.1],
            ]);

            if (!$response->successful()) {
                $status = $response->status();
                $msg    = $response->json('error.message') ?? '';
                Log::debug("Gemini HTTP $status: $msg");

                if ($status === 429)         return ['status' => 'quota'];
                if ($status === 404)         return ['status' => 'not_found'];
                if ($status === 401 || $status === 403) return ['status' => 'no_key'];
                if ($status === 400)         return ['status' => 'expired'];
                return ['status' => 'error'];
            }

            $content = $response->json('candidates.0.content.parts.0.text') ?? '';
            return $this->parseJsonContent($content);

        } catch (\Exception $e) {
            Log::error("Gemini exception: " . $e->getMessage());
            return ['status' => 'error'];
        }
    }

    // ── JSON parsing (strips markdown fences) ───────────────────────────────
    private function parseJsonContent(string $content): array
    {
        if (empty($content)) return ['status' => 'error'];

        $json = preg_replace('/^```(?:json)?\s*/m', '', $content);
        $json = preg_replace('/\s*```$/m', '', $json);
        $json = trim($json);

        $data = json_decode($json, true);
        if (!is_array($data)) {
            Log::warning('AI JSON parse failed', ['preview' => substr($content, 0, 200)]);
            return ['status' => 'error'];
        }

        return ['status' => 'ok', 'data' => $data];
    }

    // ── Prompt builder ───────────────────────────────────────────────────────
    private function buildPrompt(string $term, string $definition, ?string $sourceLang, ?string $targetLang): string
    {
        $langNote = '';
        if ($sourceLang) {
            $langMap = ['de' => 'German', 'en' => 'English', 'ru' => 'Russian', 'az' => 'Azerbaijani', 'tr' => 'Turkish', 'zh' => 'Chinese', 'ja' => 'Japanese', 'fr' => 'French', 'es' => 'Spanish'];
            $srcName = $langMap[$sourceLang] ?? $sourceLang;
            $tgtName = $langMap[$targetLang] ?? ($targetLang ?? 'unknown');
            $langNote = "The word is in {$srcName}, the translation is in {$tgtName}.";
        }

        return <<<PROMPT
Word: "{$term}"
Translation: "{$definition}"
{$langNote}

Return a JSON object with exactly these fields:
{
  "pronunciation": "IPA transcription e.g. /haʊs/, empty string if not applicable",
  "gender": "der" | "die" | "das" | null (ONLY for German nouns, null for all other cases),
  "plural_form": "plural form or empty string",
  "part_of_speech": "noun" | "verb" | "adjective" | "adverb" | "phrase" | "other",
  "examples": [{"sentence": "short example in source language (max 8 words)", "translation": "translation"}]
}

Rules: exactly 1 example sentence, gender/plural only for German nouns.
PROMPT;
    }

    // ── Apply enrichment to term ─────────────────────────────────────────────
    private function applyEnrichment(Term $term, array $data): void
    {
        $updates = [];

        if (!empty($data['pronunciation'])) {
            $updates['pronunciation'] = $data['pronunciation'];
        }
        if (!empty($data['gender']) && in_array($data['gender'], ['der', 'die', 'das'])) {
            $updates['gender'] = $data['gender'];
        }
        if (!empty($data['plural_form'])) {
            $updates['plural_form'] = $data['plural_form'];
        }
        if (!empty($data['part_of_speech']) && in_array($data['part_of_speech'], ['noun','verb','adjective','adverb','phrase','other'])) {
            $updates['part_of_speech'] = $data['part_of_speech'];
        }

        if (!empty($updates)) {
            $term->update($updates);
        }

        if (!empty($data['examples']) && is_array($data['examples'])) {
            $term->examples()->delete();
            foreach (array_slice($data['examples'], 0, 2) as $ex) {
                if (!empty($ex['sentence'])) {
                    $term->examples()->create([
                        'sentence'    => $ex['sentence'],
                        'translation' => $ex['translation'] ?? '',
                    ]);
                }
            }
        }
    }
}

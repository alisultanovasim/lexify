<?php
namespace Modules\Image\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Image\Models\TermImage;
use Modules\Image\Services\GoogleImageService;
use Modules\Image\Services\ImageStorageService;
use Modules\Vocabulary\Models\Term;

class ImageController extends Controller
{
    public function __construct(
        private GoogleImageService  $googleService,
        private ImageStorageService $storageService
    ) {}

    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|max:200']);

        $query = $this->normalizeQuery($request->q, $request->lang);

        ['images' => $results, 'error' => $error] = $this->googleService->searchWithStatus($query);

        return response()->json([
            'images'       => $results,
            'error'        => $error,
            'searched_for' => $query, // debug: what we actually searched
        ]);
    }

    /**
     * Normalize query for Pixabay:
     * 1. Strip common articles (der/die/das, le/la, el/la, etc.)
     * 2. If still non-English, translate via OpenAI
     */
    private function normalizeQuery(string $query, ?string $lang): string
    {
        $query = trim($query);

        // Strip articles for common languages
        $articlePatterns = [
            'de' => '/^(der|die|das)\s+/i',
            'fr' => '/^(le|la|les|un|une|des|l\')/i',
            'es' => '/^(el|la|los|las|un|una)\s+/i',
            'pt' => '/^(o|a|os|as|um|uma)\s+/i',
            'it' => '/^(il|lo|la|i|gli|le|un|una)\s+/i',
        ];

        if ($lang && isset($articlePatterns[$lang])) {
            $query = preg_replace($articlePatterns[$lang], '', $query);
            $query = trim($query);
        }

        // Always translate non-English to English for reliable image results.
        // Native-language Pixabay search is unreliable — e.g. German "Wort" (word)
        // matches English "wort" (a plant) — so we always go through OpenAI.
        if (!$lang || $lang === 'en') {
            return $query;
        }

        $translated = $this->translateToEnglish($query, $lang);
        return $translated ?: $query;
    }

    private function translateToEnglish(string $query, string $lang): ?string
    {
        $apiKey = config('vocabulary.openai_api_key', '');
        if (empty($apiKey)) return null;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(8)->post('https://api.openai.com/v1/chat/completions', [
                'model'      => 'gpt-4o-mini',
                'messages'   => [[
                    'role'    => 'user',
                    'content' => "Translate this word/phrase to English for image search (1-3 words, no articles, no punctuation): \"{$query}\"\nReply with ONLY the English word(s).",
                ]],
                'max_tokens' => 15,
                'temperature'=> 0,
            ]);

            $result = trim($response->json('choices.0.message.content') ?? '');
            if ($result) {
                Log::info('Image query translated', ['from' => $query, 'to' => $result, 'lang' => $lang]);
            }
            return $result ?: null;
        } catch (\Exception $e) {
            Log::warning('Image query translation failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function save(Request $request, Term $term): JsonResponse
    {
        // Manual validator — avoids Inertia middleware converting 422 to a redirect
        $url     = trim((string) $request->input('url', ''));
        $altText = (string) $request->input('alt_text', $term->term);

        if (empty($url)) {
            return response()->json(['message' => 'URL tələb olunur'], 422);
        }

        // Truncate alt_text to 255 chars server-side (Pixabay tags can be long)
        $altText = mb_substr($altText, 0, 255);

        $this->authorize('update', $term->deck);

        try {
            $image = $this->storageService->saveFromUrl($term->id, $url, $altText);
        } catch (\Exception $e) {
            Log::error('Image save failed', ['term_id' => $term->id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Şəkil saxlanarkən xəta: ' . $e->getMessage()], 422);
        }

        if (!$image) {
            return response()->json(['message' => 'Şəkil yaradıla bilmədi'], 422);
        }

        return response()->json([
            'image' => [
                'id'         => $image->id,
                'url'        => $image->url,
                'alt_text'   => $image->alt_text,
                'is_primary' => $image->is_primary,
            ],
        ]);
    }

    public function destroy(TermImage $image): JsonResponse
    {
        $this->authorize('update', $image->term->deck);
        $this->storageService->delete($image);
        return response()->json(['message' => 'Image deleted']);
    }
}

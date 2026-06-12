<?php
namespace Modules\Image\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleImageService
{
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('image.pixabay_api_key', '');
    }

    public function search(string $query, int $count = 9): array
    {
        return $this->searchWithStatus($query, $count)['images'];
    }

    public function searchWithStatus(string $query, int $count = 9): array
    {
        if (empty($this->apiKey)) {
            return ['images' => [], 'error' => 'no_api_key'];
        }

        try {
            $response = Http::get('https://pixabay.com/api/', [
                'key'        => $this->apiKey,
                'q'          => urlencode($query),
                'image_type' => 'photo',
                'per_page'   => min($count, 20),
                'safesearch' => 'true',
                'lang'       => 'en',
            ]);

            if (!$response->successful()) {
                $status = $response->status();
                Log::warning('Pixabay search failed', ['status' => $status, 'body' => $response->body()]);
                if ($status === 429) return ['images' => [], 'error' => 'quota'];
                if ($status === 400) return ['images' => [], 'error' => 'no_api_key'];
                return ['images' => [], 'error' => 'error'];
            }

            $hits = $response->json('hits', []);

            if (empty($hits)) {
                return ['images' => [], 'error' => null];
            }

            $images = array_map(function ($item) {
                $preview = $item['previewURL'] ?? '';
                // CDN URL: replace _150.jpg with _640.jpg for better quality
                // This stays permanently accessible (unlike webformatURL which expires)
                $cdnUrl = preg_replace('/_\d+(\.[a-z]+)$/', '_640$1', $preview);

                return [
                    'url'       => $cdnUrl ?: $preview, // CDN 640px (permanent)
                    'thumbnail' => $preview,             // CDN 150px (for picker display)
                    'width'     => $item['webformatWidth'] ?? 0,
                    'height'    => $item['webformatHeight'] ?? 0,
                    'title'     => $item['tags'] ?? '',
                    'context'   => $item['pageURL'] ?? '',
                ];
            }, $hits);

            return ['images' => $images, 'error' => null];

        } catch (\Exception $e) {
            Log::error('Pixabay search exception', ['error' => $e->getMessage()]);
            return ['images' => [], 'error' => 'exception'];
        }
    }
}

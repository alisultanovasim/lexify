<?php
namespace Modules\Image\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Image\Models\TermImage;

class ImageStorageService
{
    public function saveFromUrl(int $termId, string $url, string $altText = ''): ?TermImage
    {
        $fetched = $this->fetchWithFallback($url);

        if ($fetched === null) {
            Log::warning('Image download failed for all URLs', ['original' => $url]);
            return null;
        }

        try {
            $ext      = $this->extensionFromContentType($fetched['content_type']);
            $filename = 'term_images/' . $termId . '/' . Str::uuid() . '.' . $ext;

            Storage::disk('public')->put($filename, $fetched['body']);

            TermImage::where('term_id', $termId)->update(['is_primary' => false]);

            return TermImage::create([
                'term_id'      => $termId,
                'path'         => $filename,
                'original_url' => $url,
                'alt_text'     => $altText,
                'is_primary'   => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Image save failed', ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function delete(TermImage $image): void
    {
        if ($image->path && Storage::disk('public')->exists($image->path)) {
            Storage::disk('public')->delete($image->path);
        }
        $image->delete();
    }

    /**
     * Try to download the image. If the _640 URL fails, fall back to _340 then _150.
     * Pixabay CDN sometimes blocks server-side requests or lacks certain resolutions.
     */
    private function fetchWithFallback(string $url): ?array
    {
        $candidates = [$url];

        // Build fallback chain from _640 → _340 → _150
        if (preg_match('/_640(\.[a-z]+)$/i', $url)) {
            $candidates[] = preg_replace('/_640(\.[a-z]+)$/i', '_340$1', $url);
            $candidates[] = preg_replace('/_640(\.[a-z]+)$/i', '_150$1', $url);
        } elseif (preg_match('/_340(\.[a-z]+)$/i', $url)) {
            $candidates[] = preg_replace('/_340(\.[a-z]+)$/i', '_150$1', $url);
        }

        foreach ($candidates as $candidate) {
            $result = $this->download($candidate);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    private function download(string $url): ?array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept'     => 'image/webp,image/apng,image/*,*/*;q=0.8',
                    'Referer'    => 'https://pixabay.com/',
                ])
                ->get($url);

            if (!$response->successful()) {
                return null;
            }

            return [
                'body'         => $response->body(),
                'content_type' => $response->header('Content-Type') ?? 'image/jpeg',
            ];
        } catch (\Exception) {
            return null;
        }
    }

    private function extensionFromContentType(string $contentType): string
    {
        return match (true) {
            str_contains($contentType, 'jpeg') => 'jpg',
            str_contains($contentType, 'png')  => 'png',
            str_contains($contentType, 'webp') => 'webp',
            str_contains($contentType, 'gif')  => 'gif',
            default                            => 'jpg',
        };
    }
}

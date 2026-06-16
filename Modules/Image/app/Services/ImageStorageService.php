<?php
namespace Modules\Image\Services;

use Illuminate\Support\Facades\Storage;
use Modules\Image\Models\TermImage;

class ImageStorageService
{
    /**
     * Save image reference without downloading.
     * The CDN URL (cdn.pixabay.com/photo/...) is permanent — no download needed.
     * Existing images with a local path remain untouched.
     */
    public function saveFromUrl(int $termId, string $url, string $altText = ''): ?TermImage
    {
        TermImage::where('term_id', $termId)->update(['is_primary' => false]);

        return TermImage::create([
            'term_id'      => $termId,
            'path'         => null,         // No local file — served from CDN
            'original_url' => $url,
            'alt_text'     => $altText,
            'is_primary'   => true,
        ]);
    }

    /**
     * Delete image: remove local file if exists, then delete DB record.
     * Works for both old (local) and new (URL-only) images.
     */
    public function delete(TermImage $image): void
    {
        if ($image->path && Storage::disk('public')->exists($image->path)) {
            Storage::disk('public')->delete($image->path);
        }
        $image->delete();
    }
}

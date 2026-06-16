<?php
namespace Modules\Image\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Modules\Vocabulary\Models\Term;

class TermImage extends Model
{
    protected $fillable = ['term_id', 'path', 'original_url', 'alt_text', 'is_primary'];

    protected $casts = ['is_primary' => 'boolean'];

    protected $appends = ['url'];

    public function term(): BelongsTo { return $this->belongsTo(Term::class); }

    public function getUrlAttribute(): ?string
    {
        // Existing images: path set → serve from local storage
        if ($this->path) {
            return Storage::disk('public')->url($this->path);
        }
        // New images: path null → serve directly from CDN URL
        if ($this->original_url) {
            return $this->original_url;
        }
        return null;
    }
}

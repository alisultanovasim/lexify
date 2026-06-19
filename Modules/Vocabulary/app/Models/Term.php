<?php
namespace Modules\Vocabulary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Deck\Models\Deck;
use Modules\Image\Models\TermImage;

class Term extends Model
{
    protected $fillable = [
        'deck_id','term','normalized_term','definition','pronunciation',
        'part_of_speech','gender','plural_form','level','notes','position'
    ];

    protected static function booted(): void
    {
        static::saving(function (self $term) {
            if ($term->isDirty('term') || is_null($term->normalized_term)) {
                $term->normalized_term = self::normalize($term->term);
            }
        });
    }

    /**
     * Normalize a term: lowercase + strip common leading articles.
     * "das Haus" → "haus", "Auto" → "auto", "le chat" → "chat"
     */
    public static function normalize(string $term): string
    {
        $t = mb_strtolower(trim($term));
        return trim(preg_replace(
            '/^(der|die|das|le|la|les|el|los|las|il|lo|gli|un|une|a|an|the)\s+/u',
            '', $t
        ));
    }

    public function deck(): BelongsTo { return $this->belongsTo(Deck::class); }
    public function examples(): HasMany { return $this->hasMany(TermExample::class); }
    public function images(): HasMany { return $this->hasMany(TermImage::class); }
    public function primaryImage(): HasOne { return $this->hasOne(TermImage::class)->where('is_primary', true); }
}

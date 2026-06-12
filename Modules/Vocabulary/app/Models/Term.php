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
        'deck_id','term','definition','pronunciation',
        'part_of_speech','gender','plural_form','level','notes','position'
    ];

    public function deck(): BelongsTo { return $this->belongsTo(Deck::class); }
    public function examples(): HasMany { return $this->hasMany(TermExample::class); }
    public function images(): HasMany { return $this->hasMany(TermImage::class); }
    public function primaryImage(): HasOne { return $this->hasOne(TermImage::class)->where('is_primary', true); }
}

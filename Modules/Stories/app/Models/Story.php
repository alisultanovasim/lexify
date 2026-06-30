<?php
namespace Modules\Stories\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Modules\Deck\Models\Deck;
use Modules\Language\Models\Language;

class Story extends Model
{
    protected $fillable = ['user_id', 'title', 'body', 'level', 'deck_id', 'language_id', 'audio_path'];

    protected $appends = ['audio_url'];

    public function getAudioUrlAttribute(): ?string
    {
        return $this->audio_path ? Storage::url($this->audio_path) : null;
    }

    public function user(): BelongsTo { return $this->belongsTo(\App\Models\User::class); }
    public function deck(): BelongsTo { return $this->belongsTo(Deck::class); }
    public function language(): BelongsTo { return $this->belongsTo(Language::class); }
    public function reads(): HasMany { return $this->hasMany(StoryRead::class); }
}

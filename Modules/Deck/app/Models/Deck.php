<?php
namespace Modules\Deck\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Language\Models\Language;
use Modules\Vocabulary\Models\Term;

class Deck extends Model
{
    protected $fillable = ['user_id','source_language_id','target_language_id','title','description','color','is_public'];

    protected $casts = ['is_public' => 'boolean'];

    public function user(): BelongsTo { return $this->belongsTo(\App\Models\User::class); }
    public function sourceLanguage(): BelongsTo { return $this->belongsTo(Language::class, 'source_language_id'); }
    public function targetLanguage(): BelongsTo { return $this->belongsTo(Language::class, 'target_language_id'); }
    public function terms(): HasMany { return $this->hasMany(Term::class)->orderBy('position'); }
}

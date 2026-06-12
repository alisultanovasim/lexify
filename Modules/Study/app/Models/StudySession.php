<?php
namespace Modules\Study\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Deck\Models\Deck;

class StudySession extends Model
{
    protected $fillable = [
        'user_id','deck_id','mode','completed_at',
        'total_cards','correct_count','incorrect_count'
    ];

    protected $casts = ['completed_at' => 'datetime'];

    public function user(): BelongsTo { return $this->belongsTo(\App\Models\User::class); }
    public function deck(): BelongsTo { return $this->belongsTo(Deck::class); }
    public function answers(): HasMany { return $this->hasMany(StudyAnswer::class); }
}

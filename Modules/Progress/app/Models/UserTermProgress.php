<?php
namespace Modules\Progress\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Vocabulary\Models\Term;

class UserTermProgress extends Model
{
    protected $table = 'user_term_progress';

    protected $fillable = [
        'user_id','term_id','ease_factor','interval',
        'repetitions','next_review_at','last_reviewed_at'
    ];

    protected $casts = [
        'next_review_at'   => 'datetime',
        'last_reviewed_at' => 'datetime',
    ];

    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
    public function user(): BelongsTo { return $this->belongsTo(\App\Models\User::class); }
}

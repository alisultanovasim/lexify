<?php
namespace Modules\Vocabulary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermExample extends Model
{
    protected $fillable = ['term_id', 'sentence', 'translation'];

    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
}

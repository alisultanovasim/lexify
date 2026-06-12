<?php
namespace Modules\Study\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Vocabulary\Models\Term;

class StudyAnswer extends Model
{
    protected $fillable = ['study_session_id','term_id','is_correct','response_time_ms'];

    protected $casts = ['is_correct' => 'boolean'];

    public function session(): BelongsTo { return $this->belongsTo(StudySession::class, 'study_session_id'); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
}

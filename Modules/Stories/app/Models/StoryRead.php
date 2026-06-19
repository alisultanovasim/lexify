<?php
namespace Modules\Stories\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoryRead extends Model
{
    protected $fillable = ['user_id', 'story_id', 'read_at'];

    public function story(): BelongsTo { return $this->belongsTo(Story::class); }
    public function user(): BelongsTo { return $this->belongsTo(\App\Models\User::class); }
}

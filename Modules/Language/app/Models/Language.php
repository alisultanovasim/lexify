<?php
namespace Modules\Language\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $fillable = ['code', 'name', 'native_name', 'flag_emoji', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}

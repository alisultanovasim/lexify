<?php
namespace Modules\Language\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Language\Models\Language;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['code' => 'az', 'name' => 'Azerbaijani', 'native_name' => 'Azərbaycan', 'flag_emoji' => '🇦🇿'],
            ['code' => 'de', 'name' => 'German',       'native_name' => 'Deutsch',    'flag_emoji' => '🇩🇪'],
            ['code' => 'en', 'name' => 'English',      'native_name' => 'English',    'flag_emoji' => '🇬🇧'],
            ['code' => 'ru', 'name' => 'Russian',      'native_name' => 'Русский',    'flag_emoji' => '🇷🇺'],
            ['code' => 'tr', 'name' => 'Turkish',      'native_name' => 'Türkçe',     'flag_emoji' => '🇹🇷'],
        ];

        foreach ($languages as $lang) {
            Language::firstOrCreate(['code' => $lang['code']], $lang);
        }
    }
}

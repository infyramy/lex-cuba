<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['key' => 'siteTitle',       'value' => 'LexSZA'],
            ['key' => 'tagline',         'value' => 'UNISZA MyLegS Legal Studies Platform'],
            ['key' => 'siteIconUrl',     'value' => ''],
            ['key' => 'sidebarLogoUrl',  'value' => ''],
            ['key' => 'faviconUrl',      'value' => ''],
            ['key' => 'language',        'value' => 'en'],
            ['key' => 'timezone',        'value' => 'Asia/Kuala_Lumpur'],
            ['key' => 'footerText',      'value' => ''],
            ['key' => 'companyName',     'value' => 'UNISZA'],
            ['key' => 'companyAddress',  'value' => ''],
            ['key' => 'brandColor',              'value' => '#5469d4'],
            ['key' => 'aboutContent',            'value' => ''],
            ['key' => 'maintenanceMode',         'value' => 'false'],
            ['key' => 'supportEmail',            'value' => ''],
            ['key' => 'supportPhone',            'value' => ''],
            ['key' => 'websiteUrl',              'value' => ''],
            ['key' => 'facebookUrl',             'value' => ''],
            ['key' => 'instagramUrl',            'value' => ''],
            ['key' => 'twitterUrl',              'value' => ''],
            ['key' => 'linkedinUrl',             'value' => ''],
            ['key' => 'termsUrl',                'value' => ''],
            ['key' => 'privacyUrl',              'value' => ''],
            ['key' => 'appName',                 'value' => 'LexSZA'],
            ['key' => 'upgradePromptMessage',    'value' => 'Upgrade to a premium plan to access all study materials and features.'],
            ['key' => 'minimumAppVersion',       'value' => '1.0.0'],
            ['key' => 'appStoreUrl',             'value' => ''],
            ['key' => 'googlePlayUrl',           'value' => ''],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], ['value' => $setting['value']]);
        }

        // Remove stale old-CMS keys
        Setting::whereIn('key', [
            'webfrontTitle', 'webfrontTagline', 'titleFormat',
            'metaDescription', 'webfrontLogoUrl', 'frontPageId',
        ])->delete();
    }
}

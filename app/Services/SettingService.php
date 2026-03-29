<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SettingService
{
    /**
     * Default setting keys and their default values.
     */
    protected array $defaults = [
        'siteTitle'      => 'LexSZA',
        'tagline'        => 'UNISZA MyLegS Legal Studies Platform',
        'siteIconUrl'    => '',
        'sidebarLogoUrl' => '',
        'faviconUrl'     => '',
        'language'       => 'en',
        'timezone'       => 'Asia/Kuala_Lumpur',
        'footerText'     => '',
        'companyName'    => 'UNISZA',
        'companyAddress' => '',
        'brandColor'     => '#5469d4',
    ];

    /**
     * Key aliases for backward compatibility.
     *
     * @var array<string, array<int, string>>
     */
    protected array $aliases = [
        'siteTitle'      => ['siteTitle', 'site_title'],
        'tagline'        => ['tagline'],
        'siteIconUrl'    => ['siteIconUrl', 'site_icon_url'],
        'sidebarLogoUrl' => ['sidebarLogoUrl', 'sidebar_logo_url'],
        'faviconUrl'     => ['faviconUrl', 'favicon_url'],
        'language'       => ['language'],
        'timezone'       => ['timezone'],
        'footerText'     => ['footerText', 'footer_text'],
        'companyName'    => ['companyName', 'company_name'],
        'companyAddress' => ['companyAddress', 'company_address'],
        'brandColor'     => ['brandColor', 'brand_color'],
    ];

    /**
     * Retrieve all settings as a key-value array, applying defaults for missing keys.
     *
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        $rows = DB::table('settings')->pluck('value', 'key')->toArray();

        $result = [];
        foreach ($this->defaults as $key => $default) {
            $result[$key] = $this->resolveValueByAlias($key, $rows, $default);
        }

        return $result;
    }

    /**
     * Update multiple settings at once, upserting each key within a transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(array $data): void
    {
        DB::transaction(function () use ($data) {
            foreach ($data as $key => $value) {
                $stringValue = $this->serializeValue($value);

                DB::table('settings')->updateOrInsert(
                    ['key' => $key],
                    ['value' => $stringValue]
                );

                // Remove legacy alias keys to prevent shadowing
                $aliasList = $this->aliases[$key] ?? [];
                foreach ($aliasList as $alias) {
                    if ($alias !== $key) {
                        DB::table('settings')->where('key', $alias)->delete();
                    }
                }
            }
        });
    }

    /**
     * Retrieve a single setting value.
     *
     * @param  mixed|null  $default
     */
    public function get(string $key, $default = null): ?string
    {
        $keys = $this->aliases[$key] ?? [$key];
        foreach ($keys as $candidate) {
            $row = DB::table('settings')->where('key', $candidate)->first();
            if ($row) {
                return $row->value;
            }
        }

        return $default;
    }

    /**
     * Set a single setting value.
     */
    public function set(string $key, string $value): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Serialize a value for storage in the settings table.
     *
     * @param  mixed  $value
     */
    protected function serializeValue($value): string
    {
        if (is_null($value)) {
            return 'null';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }

    /**
     * Resolve a canonical setting key from DB rows and legacy aliases.
     *
     * @param  array<string, mixed>  $rows
     * @param  mixed  $default
     * @return mixed
     */
    protected function resolveValueByAlias(string $key, array $rows, $default)
    {
        $candidates = $this->aliases[$key] ?? [$key];

        foreach ($candidates as $candidate) {
            if (! array_key_exists($candidate, $rows)) {
                continue;
            }

            $value = $rows[$candidate];

            if ($key === 'frontPageId') {
                if ($value === null || $value === '' || $value === 'null') {
                    return null;
                }

                return (int) $value;
            }

            if ($value === 'null') {
                return null;
            }

            return $value;
        }

        return $default;
    }
}

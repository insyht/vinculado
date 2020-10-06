<?php

namespace Vinculado\Helpers;

use Vinculado\Services\SettingsService;

class SyncHelper
{
    /**
     * Shop is a master if all of these conditions are true:
     * - The master URL setting exists
     * - The master URL setting is an empty string
     *In all other cases, this shop is a slave
     *
     * @return bool
     */
    public static function shopIsMaster(): bool
    {
        $masterUrlValue = get_option(SettingsService::SETTING_MASTER_TOKEN, '');

        return  $masterUrlValue !== false && $masterUrlValue === '';
    }

    /**
     * Inverse of shopIsMaster()
     *
     * @return bool
     */
    public static function shopIsSlave(): bool
    {
        return !self::shopIsMaster();
    }

    public static function generateApiToken(): string
    {
        $siteUrl = get_site_url();
        $key = sha1(microtime(true) . mt_rand(10000, 90000));
        $token = base64_encode($siteUrl) . '.' . $key;
        update_option(SettingsService::SETTING_API_TOKEN, $token);

        return get_option(SettingsService::SETTING_API_TOKEN, null);
    }

    public static function getSiteFromToken(string $token): ?string
    {
        $site = null;

        $explode = explode('.', $token);
        if (array_key_exists(0, $explode)) {
            $encodedSite = $explode[0];
            $site = base64_decode($encodedSite) ?? null;
        }

        return $site;
    }
}

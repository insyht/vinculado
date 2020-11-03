<?php

namespace Vinculado\Services;

use stdClass;

/**
 * Class UpdaterService
 * @package Vinculado
 */
class UpdaterService
{
    public const REQUEST_TYPE_GET = 'GET';
    public const REQUEST_TYPE_POST = 'POST';

    protected $githubUrl;
    protected $debug;
    protected $slug;
    protected $returnType;
    protected $userAgent;

    public function __construct()
    {
        $this->githubUrl = 'https://api.github.com/repos/insyht/vinculado/';
        $this->debug = WP_DEBUG;
        $this->slug = 'vinculado/vinculado.php';
        $this->returnType = 'application/vnd.github.v3+json';
        $this->userAgent = 'VinculadoUpdater';

        add_filter('pre_set_site_transient_update_plugins', [$this, 'updateCheck']);
        add_filter('plugins_api', [$this, 'getPluginData'], 10, 3);
        add_filter(
            'http_request_timeout',
            function () {
                return 2;
            }
        );
    }

    public static function runUpdater(): void
    {
        new self();
    }

    public function call(string $endpoint = '', string $type = self::REQUEST_TYPE_GET, array $data = []): array
    {
        $requestUrl = sprintf('%s%s', $this->githubUrl, $endpoint);
        $headers = [
            sprintf('Accept: %s', $this->returnType),
            "Cookie: XDEBUG_SESSION=PHPSTORM",
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $requestUrl);
        curl_setopt($curl, CURLOPT_USERAGENT, $this->userAgent);

        if ($type === self::REQUEST_TYPE_POST) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if ($this->debug) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        return [
            'response' => $response,
            'errors' => $error,
        ];
    }

    public function updateCheck(object $transient): object
    {
        if (!empty($transient->checked)) {
            if ($this->isUpdateNeeded()) {
                $updateData = new stdClass();
                $updateData->version = $this->getCurrentVersionNumber();
                $updateData->new_version = $this->getLatestVersionNumber();
                $updateData->slug = $this->slug;
                $updateData->url = $this->githubUrl;
                $updateData->package = $this->getZipUrl();

                $transient->response[$this->slug] = $updateData;
            }
        }

        return $transient;
    }

    protected function getLatestVersionNumber(): string
    {
        $data = $this->call('readme');
        $readmeContents = base64_decode(json_decode($data['response'], true)['content']);

        return $this->extractVersionNumberFromReadme($readmeContents);
    }

    protected function getCurrentVersionNumber(): string
    {
        $readmeContents = file_get_contents(sprintf('%s/../README.md', __DIR__));

        return $this->extractVersionNumberFromReadme($readmeContents);
    }

    protected function extractVersionNumberFromReadme(string $readmeContents): string
    {
        preg_match('/(Version: [0-9.]+)/', $readmeContents, $matches);

        return str_replace('Version: ', '', $matches[1]);
    }

    protected function isUpdateNeeded(): bool
    {
        return version_compare($this->getCurrentVersionNumber(), $this->getLatestVersionNumber()) === -1;
    }

    protected function getZipUrl(): string
    {
        return sprintf('%szipball', $this->githubUrl);
    }

    /**
     * @param bool   $false
     * @param string $action
     * @param object $pluginData
     *
     * @return false|object
     */
    public function getPluginData(bool $false, string $action, object $pluginData)
    {
        if (!isset($pluginData->slug) || $pluginData->slug !== $this->slug) {
            return false;
        }

        $pluginData->version = $this->getCurrentVersionNumber();
        $pluginData->new_version = $this->getLatestVersionNumber();
        $pluginData->download_link = $this->getZipUrl();

        return $pluginData;
    }
}

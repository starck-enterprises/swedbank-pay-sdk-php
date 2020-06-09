<?php

namespace SwedbankPay\Api\Client;

use \SwedbankPay\Api\Client\Exception as ClientException;

/**
 * Class ClientVersion
 * @package SwedbankPay\Api\Client
 */
class ClientVersion
{
    private $clientVersion;

    /**
     * Version constructor
     *
     * @throws ClientException
     */
    public function __construct()
    {
        $this->clientVersion = $this->getVersionFromEnvironment();
    }

    /**
     * Gets version constant name
     *
     * @return string
     */
    private function getVersionConstName()
    {
        return __NAMESPACE__ . '\\VERSION';
    }

    /**
     * Gets version environment variable name
     *
     * @return string
     */
    private function getVersionEnvName()
    {
        return str_replace('\\', '_', strtoupper($this->getVersionConstName()));
    }

    /**
     * Gets path to composer package root directory
     *
     * @return string
     */
    private function getComposerPath()
    {
        $autoLoadPath = '/src/' . str_replace('\\', '/', __NAMESPACE__);
        if (DIRECTORY_SEPARATOR !== '/') {
            $autoLoadPath = str_replace('/', DIRECTORY_SEPARATOR, $autoLoadPath);
        }

        return str_replace($autoLoadPath, '', __DIR__);
    }

    /**
     * Gets the version number from a defined constant VERSION, the environment
     * variable VERSION or from composer.json.
     *
     * @return string Version number
     */
    public function getVersion() : string
    {
        return $this->clientVersion;
    }

    /**
     * Gets the version number from a defined constant VERSION, the environment
     * variable VERSION or from composer.json.
     *
     * @return string Version number
     * @throws ClientException
     */
    protected function getVersionFromEnvironment() : string
    {
        $version = null;

        if ($this->tryGetVersionNumberFromConstant($version)) {
            return $version;
        }

        if ($this->tryGetVersionNumberFromEnv($version)) {
            return $version;
        }

        if ($this->tryGetVersionNumberFromComposerJson($version)) {
            return $version;
        }

        if ($this->tryGetVersionNumberFromComposerLock($version)) {
            return $version;
        }

        // phpcs:disable
        throw new ClientException('VERSION not found in constant variable ' . $this->getVersionConstName() . ' , composer.json or anywhere else.');
        // phpcs:enable
    }

    /**
      * Tries to get the version number from possible (namespaced) VERSION constant.
      * Returns true if successful; otherwise false.
      *
      * @param string $version The by-reference $version variable to assign the version number to, if found.
      * @return bool true if successful; otherwise false.
      */
    private function tryGetVersionNumberFromConstant(&$version) : bool
    {
        if (defined($this->getVersionConstName()) && constant($this->getVersionConstName()) !== '') {
            $version = constant($this->getVersionConstName());
            return true;
        }

        return false;
    }

    /**
      * Tries to get the version number from possible environment variable.
      * Returns true if successful; otherwise false.
      *
      * @param string $version The by-reference $version variable to assign the version number to, if found.
      * @return bool true if successful; otherwise false.
      */
    private function tryGetVersionNumberFromEnv(&$version) : bool
    {
        // phpcs:disable
        $envVersion = getenv($this->getVersionEnvName());
        // phpcs:enable

        if ($envVersion !== false && $envVersion !== null && !empty($envVersion)) {
            $version = $envVersion;
            return true;
        }

        return false;
    }

    /**
      * Tries to get the version number from the composer.json file.
      * Returns true if successful; otherwise false.
      *
      * @param string $version The by-reference $version variable to assign the version number to, if found.
      * @return bool true if successful; otherwise false.
      */
    private function tryGetVersionNumberFromComposerJson(&$version) : bool
    {
        $composer = null;

        if (!$this->tryReadComposerJson($composer)) {
            return false;
        }

        if (isset($composer['version'])) {
            $version = $composer['version'];

            if ($version !== null && !empty($version)) {
                return true;
            }
        }

        return false;
    }

    /**
      * Tries to get the version number from the composer.lock file.
      * Returns true if successful; otherwise false.
      *
      * @param string $version The by-reference $version variable to assign the version number to, if found.
      * @return bool true if successful; otherwise false.
      * @SuppressWarnings(PHPMD.CyclomaticComplexity)
      */
    private function tryGetVersionNumberFromComposerLock(&$version) : bool
    {
        $paths = [];

        // Standard path of composer.lock
        $path = base_path('composer.lock');
        $paths[] = $path;

        // Alternate path of composer.lock
        $pathDirs = explode(DIRECTORY_SEPARATOR, $path);
        array_splice($pathDirs, -2);
        $pathDirs[] = 'composer.lock';
        $paths[] = implode(DIRECTORY_SEPARATOR, $pathDirs);

        foreach ($paths as $path) {
            $composerLock = null;
           // $result = $this->tryFindVersionInComposerLock($composerLock);
            //if ($this->tryReadComposerLock($composerLock, $path) && $result) {
            if ($this->tryReadComposerLock($composerLock, $path) &&
                $result = $this->tryFindVersionInComposerLock($composerLock)
            ) {
                $version = $result;
                return true;
            }
        }

        return false;
    }

    /**
     * Tries to find composer.json file and assigns a JSON decoded object to
     * $json if successful. Returns true if successful; otherwise false.
     *
     * @param object $decodedJsonObject The by-reference variable to assign the JSON decoded object to.
     * @return bool true if the JSON decoding is successful; otherwise false.
     */
    private function tryReadComposerJson(&$decodedJsonObject) : bool
    {
        $path = $this->getComposerPath() . DIRECTORY_SEPARATOR . 'composer.json';

        // phpcs:disable
        if (!file_exists($path)) {
            return false;
        }

        $contents = file_get_contents($path);

        // phpcs:enable
        $decodedJsonObject = json_decode($contents, true);

        return true;
    }

    /**
     * Tries to find composer.lock file and assigns a JSON decoded object to
     * $json if successful. Traverses up in directory tree if not found in
     * composer package root path because we might be part of another composer install.
     * Returns true if successful; otherwise false.
     *
     * @param object $decodedJsonObject The by-reference variable to assign the JSON decoded object to.
     * @param string $path Optional alternative path to look for composer lock file.
     * @return bool true if the JSON decoding is successful; otherwise false.
     */
    private function tryReadComposerLock(&$decodedJsonObject, $path = '') : bool
    {
        // phpcs:disable
        if (!is_readable($path)) {
            return false;
        }
        // phpcs:enable

        // phpcs:disable
        $contents = file_get_contents($path);

        // phpcs:enable
        $decodedJsonObject = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        return true;
    }

    /**
     * Tries to find the version of package in composer.lock file.
     *
     * @param array $decodedJsonObject
     * @return string|false
     */
    private function tryFindVersionInComposerLock($decodedJsonObject)
    {
        if (!isset($decodedJsonObject['packages'])) {
            return false;
        }

        $packages = $decodedJsonObject['packages'];
        foreach ($packages as $package) {
            if (!isset($package['version']) ||
                !isset($package['name']) ||
                $package['name'] !== 'starck-enterprises/swedbank-pay-sdk-php'
            ) {
                continue;
            }

            return $package['version'];
        }

        return false;
    }
}

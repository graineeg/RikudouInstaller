<?php

namespace Rikudou\Installer\Configuration;

use Composer\Composer;
use Composer\Package\PackageInterface;
use function Rikudou\Installer\array_merge_recursive;
use Rikudou\Installer\Exception\ConfigurationException;
use Rikudou\Installer\ProjectType\ProjectTypeInterface;

final class Config
{
    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var array
     */
    private $config = [];

    /**
     * @var string
     */
    private $file = null;

    /**
     * Versions constructor.
     *
     * @param Composer $composer
     *
     * @throws ConfigurationException
     */
    public function __construct(Composer $composer)
    {
        $this->composer = $composer;

        $this->file = dirname($composer->getConfig()->getConfigSource()->getName()) . '/rikudou.installer.lock';
        if (!file_exists($this->file)) {
            if (!@file_put_contents($this->file, json_encode([
                'COMMENT' => 'THIS FILE SHOULD BE COMMITTED TO YOUR SOURCE CONTROL SOFTWARE',
            ], JSON_PRETTY_PRINT))) {
                throw new ConfigurationException(sprintf('Could not create config file at %s', dirname($composer->getConfig()->getConfigSource()->getName())));
            }
        }
        $this->config = json_decode(file_get_contents($this->file), true);
    }

    public function getLastConfiguredVersion(PackageInterface $package): string
    {
        return $this->config[$package->getName()]['version'] ?? '0';
    }

    /**
     * @param PackageInterface     $package
     * @param ProjectTypeInterface $projectType
     *
     * @return VersionDirectory[]
     */
    public function getPackageVersions(PackageInterface $package, ProjectTypeInterface $projectType): array
    {
        $path = $this->composer->getInstallationManager()->getInstallPath($package) . '/.installer';
        if (!file_exists($path)) {
            return [];
        }

        /** @var VersionDirectory[] $versions */
        $versions = [];
        $versionRegex = '@^v?\d+(?:\.\d+(?:\.\d+)?)?$@';
        foreach ($projectType->getProjectDirs() as $projectDir) {
            $projectTypePath = "{$path}/{$projectDir}";
            if (!file_exists($projectTypePath)) {
                continue;
            }
            /** @var string[] $projectTypeFiles */
            $projectTypeFiles = scandir($projectTypePath);
            foreach ($projectTypeFiles as $projectTypeFile) {
                if (is_dir("{$projectTypePath}/{$projectTypeFile}") && preg_match($versionRegex, $projectTypeFile)) {
                    $versions[] = new VersionDirectory(str_replace('v', '', $projectTypeFile), "{$projectTypePath}/{$projectTypeFile}");
                }
            }
        }

        usort($versions, function ($version1, $version2) {
            assert($version1 instanceof VersionDirectory);
            assert($version2 instanceof VersionDirectory);

            return version_compare($version1->getVersion(), $version2->getVersion());
        });

        return $versions;
    }

    /**
     * @param PackageInterface     $package
     * @param ProjectTypeInterface $projectType
     *
     * @return string[]
     */
    public function getInstallableVersions(PackageInterface $package, ProjectTypeInterface $projectType): array
    {
        $lastConfiguredVersion = $this->getLastConfiguredVersion($package);

        $versions = [];
        foreach ($this->getPackageVersions($package, $projectType) as $packageVersion) {
            if (version_compare($lastConfiguredVersion, $packageVersion->getVersion(), '<')) {
                $versions[] = $packageVersion;
            }
        }

        return $versions;
    }

    public function getConfig(PackageInterface $package)
    {
        return $this->config[$package->getName()] ?? [];
    }

    public function addConfig(PackageInterface $package, array $config)
    {
        $this->config[$package->getName()] = array_merge_recursive($this->config[$package->getName()] ?? [], $config);

        return $this;
    }

    public function removeConfig(PackageInterface $package)
    {
        if (isset($this->config[$package->getName()])) {
            unset($this->config[$package->getName()]);
        }
    }

    public function flush()
    {
        file_put_contents($this->file, json_encode($this->config, JSON_PRETTY_PRINT));
    }
}

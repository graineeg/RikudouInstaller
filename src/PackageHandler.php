<?php

namespace Rikudou\Installer;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Rikudou\Installer\Configuration\Config;
use Rikudou\Installer\Configuration\VersionDirectory;
use Rikudou\Installer\Helper\AvailabilityAwareOperationInterface;
use Rikudou\Installer\Helper\SupportedProjectTypesInterface;
use Rikudou\Installer\Operations\AbstractOperation;
use Rikudou\Installer\ProjectType\ProjectTypeInterface;
use Rikudou\Installer\Result\OperationResultCollection;

final class PackageHandler
{
    /**
     * @var PackageInterface
     */
    private $package;

    /**
     * @var ProjectTypeInterface
     */
    private $projectType;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var Config
     */
    private $config;

    /**
     * PackageHandler constructor.
     *
     * @param PackageInterface     $package
     * @param ProjectTypeInterface $projectType
     * @param Composer             $composer
     * @param Config               $config
     */
    public function __construct(
        PackageInterface $package,
        ProjectTypeInterface $projectType,
        Composer $composer,
        Config $config
    ) {
        $this->package = $package;
        $this->projectType = $projectType;
        $this->composer = $composer;
        $this->config = $config;
    }

    /**
     * Checks whether package contains .installer dir and thus can be handled
     *
     * @return bool
     */
    public function containsInstallerDirectory(): bool
    {
        return is_dir("{$this->composer->getInstallationManager()->getInstallPath($this->package)}/.installer");
    }

    /**
     * Checks for supported project type operations and returns array of OperationResult instances
     *
     * @return OperationResultCollection
     */
    public function handleInstall(): iterable
    {
        $results = new OperationResultCollection();

        $handlers = AbstractOperation::getOperationHandlers($this->composer);
        $handlersMap = [];

        foreach ($handlers as $handler) {
            $handler = new $handler($this->package, $this->projectType, $this->composer);
            if ($handler instanceof SupportedProjectTypesInterface) {
                if (in_array($this->projectType->getMachineName(), $handler->getSupportedProjectTypes())) {
                    $handlersMap[] = $handler;
                }
            }
        }

        foreach ($this->projectType->getTypes() as $type) {
            if (isset($handlers[$type])) {
                $class = $handlers[$type];
                /** @var AbstractOperation $handler */
                $handler = new $class($this->package, $this->projectType, $this->composer);
                $handlersMap[] = $handler;
            }
        }

        $versions = $this->getVersions();
        foreach ($handlersMap as $handler) {
            if ($handler instanceof AvailabilityAwareOperationInterface) {
                if (!$handler->isAvailable($this->getPaths())) {
                    continue;
                }
            }

            foreach ($versions as $version) {
                $result = $handler->install($version->getPath());
                if (!$result->isNeutral()) {
                    $this->config->addConfig($this->package, [
                        'operations' => [
                            $version->getVersion() => [
                                $handler->handles() => [],
                            ],
                        ],
                    ]);
                    if ($extraConfig = $result->getExtraConfig()) {
                        $this->config->addConfig($this->package, [
                            'operations' => [
                                $version->getVersion() => [
                                    $handler->handles() => $extraConfig,
                                ],
                            ],
                        ]);
                    }
                    $result->setVersion('v' . $version->getVersion());
                    $result->setOperationName($handler->getFriendlyName());
                    $results[] = $result;
                }
            }
        }

        if (isset($version)) {
            $this->config->addConfig($this->package, [
                'version' => $version->getVersion(),
            ]);
        }

        return $results;
    }

    /**
     * Checks for supported project type operations and returns array of OperationResult instances
     *
     * @return OperationResultCollection
     */
    public function handleUninstall(): iterable
    {
        $results = new OperationResultCollection();

        $handlers = AbstractOperation::getOperationHandlers($this->composer);

        $paths = $this->getPaths(true);
        foreach ($this->projectType->getTypes() as $type) {
            if (isset($handlers[$type])) {
                $class = $handlers[$type];
                /** @var AbstractOperation $handler */
                $handler = new $class($this->package, $this->projectType, $this->composer);
                if ($handler instanceof AvailabilityAwareOperationInterface) {
                    if (!$handler->isAvailable($paths)) {
                        continue;
                    }
                }
                $versions = $this->getVersions(true);
                foreach ($versions as $version) {
                    $config = $this->config->getConfig($this->package)['operations'][$version->getVersion()][$handler->handles()] ?? [];
                    $result = $handler->uninstall($version->getPath(), $config);
                    if (!$result->isNeutral()) {
                        $result->setVersion('v' . $version->getVersion());
                        $result->setOperationName($handler->getFriendlyName());
                        $results[] = $result;
                    }
                }
            }
        }

        $this->config->removeConfig($this->package);

        return $results;
    }

    /**
     * @param bool $all
     *
     * @return string[]
     */
    private function getPaths(bool $all = false): array
    {
        $installableVersions = $this->getVersions($all);
        $paths = array_map(function ($item) {
            assert($item instanceof VersionDirectory);

            return $item->getPath();
        }, $installableVersions);

        return $paths;
    }

    /**
     * @param bool $all
     *
     * @return VersionDirectory[]
     */
    private function getVersions(bool $all = false): array
    {
        if ($all) {
            return $this->config->getPackageVersions($this->package, $this->projectType);
        } else {
            return $this->config->getInstallableVersions($this->package, $this->projectType);
        }
    }
}

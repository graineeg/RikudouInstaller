<?php

namespace Rikudou\Installer\Operations;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Rikudou\Installer\Helper\ClassInfoParser;
use Rikudou\Installer\ProjectType\ProjectTypeInterface;

abstract class OperationHandlerBase
{

    /**
     * @var ProjectTypeInterface
     */
    protected $projectType;

    /**
     * @var string
     */
    protected $projectRootDir;

    /**
     * @var string
     */
    protected $packageInstallDir;

    /**
     * @var string
     */
    protected $packageName;

    public function __construct(PackageInterface $package, ProjectTypeInterface $projectType, Composer $composer)
    {
        $this->projectType = $projectType;

        $this->projectRootDir = dirname($composer->getConfig()->getConfigSource()->getName());
        $this->packageInstallDir = $composer->getInstallationManager()->getInstallPath($package);
        $this->packageName = $package->getName();
    }

    /**
     * Handle installation for given operation
     *
     * @return OperationResult
     */
    abstract public function install(): OperationResult;

    /**
     * Handle uninstallation for given operation
     *
     * @return OperationResult
     */
    abstract public function uninstall(): OperationResult;

    /**
     * Returns the type of operation this class can handle.
     * At this method the constructor parameters are not yet available.
     *
     * @return string
     */
    abstract public function handles(): string;

    /**
     * @return string[]
     */
    public static function getHandlers(): array
    {
        $handlers = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(__DIR__)
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === "php") {
                $classInfo = new ClassInfoParser($file->getRealPath());
                if ($classInfo->isInstanceOf(self::class) && $classInfo->isInstantiable()) {
                    /** @var self $handler */
                    $handler = $classInfo->getReflection()->newInstanceWithoutConstructor();
                    $handlers[$handler->handles()] = $classInfo->getClassName();
                }
            }
        }

        return $handlers;
    }

}
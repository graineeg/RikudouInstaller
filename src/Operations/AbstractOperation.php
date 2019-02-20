<?php

namespace Rikudou\Installer\Operations;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Rikudou\Installer\Helper\PreloadInterface;
use Rikudou\Installer\ProjectType\ProjectTypeInterface;
use Rikudou\Installer\Result\OperationResult;
use Rikudou\ReflectionFile;

abstract class AbstractOperation implements PreloadInterface
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

    private static $handlers = null;

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
     * @param Composer $composer
     *
     * @return string[]
     */
    public static function getOperationHandlers(Composer $composer): array
    {
        $handlers = [];
        /** @var string[] $directories */
        $directories = [];

        foreach ($composer->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {
            $path = $composer->getInstallationManager()->getInstallPath($package);
            if (file_exists("{$path}/.installer/operations")) {
                $directories[] = "{$path}/.installer/operations";
            }
        }

        $directories[] = __DIR__;

        try {
            foreach ($directories as $directory) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($directory)
                );

                /** @var \SplFileInfo $file */
                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getExtension() === 'php') {
                        try {
                            $filePath = $file->getRealPath();
                            if (!$filePath) {
                                continue;
                            }
                            $reflectionFile = new ReflectionFile($filePath);
                            if ($reflectionFile->containsClass()) {
                                $reflectionClass = $reflectionFile->getClass();
                                if ($reflectionClass->isSubclassOf(self::class) && $reflectionClass->isInstantiable()) {
                                    /** @var self $handler */
                                    $handler = $reflectionClass->newInstanceWithoutConstructor();
                                    $handlers[$handler->handles()] = $reflectionClass->getName();
                                }
                            }
                        } catch (\ReflectionException $e) {
                            // ignore
                        }
                    }
                }
            }
        } catch (\UnexpectedValueException $exception) {
            $handlers = self::$handlers;
        }

        return $handlers;
    }

    public static function preload(Composer $composer): void
    {
        if (is_null(self::$handlers)) {
            self::$handlers = static::getOperationHandlers($composer);
        }
    }
}

<?php

namespace Rikudou\Installer\ProjectType;

use Composer\Composer;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionException;
use rikudou\ArraySort;
use Rikudou\Installer\Helper\PreloadInterface;
use Rikudou\ReflectionFile;
use SplFileInfo;
use UnexpectedValueException;

final class ProjectTypeMatcher implements PreloadInterface
{
    private static $classes = null;

    /**
     * Returns the project type, either from composer extra section or it tries to detect from file structure.
     * Returns null if no project type is detected.
     *
     * @param Composer $composer
     *
     * @return null|ProjectTypeInterface
     */
    public static function findProjectType(Composer $composer): ?ProjectTypeInterface
    {
        $rootDir = dirname($composer->getConfig()->getConfigSource()->getName());

        $classes = static::getProjectTypeClasses($composer);

        $composerProjectType = $composer->getPackage()->getExtra()['rikudou']['installer']['project-type'] ?? null;
        if (!is_null($composerProjectType) && isset($classes[$composerProjectType])) {
            $class = $classes[$composerProjectType];

            return new $class;
        }

        $classes = (new ArraySort($classes))
            ->byValue()
            ->discardKey()
            ->customSort(function ($class1, $class2) {
                $instance1 = new $class1;
                $instance2 = new $class2;
                assert($instance1 instanceof ProjectTypeInterface);
                assert($instance2 instanceof ProjectTypeInterface);

                if ($instance1->getPriority() === $instance2->getPriority()) {
                    return 0;
                }

                return $instance1->getPriority() < $instance2->getPriority() ? -1 : 1;
            });

        foreach ($classes as $class) {
            /** @var ProjectTypeInterface $instance */
            $instance = new $class;
            foreach ($instance->getMatchableFiles() as $dir) {
                if (is_array($dir)) {
                    $exists = true;
                    foreach ($dir as $requiredDir) {
                        $exists = $exists && file_exists("{$rootDir}/{$requiredDir}");
                    }
                    if ($exists) {
                        return $instance;
                    }
                } else {
                    if (file_exists("{$rootDir}/{$dir}")) {
                        return $instance;
                    }
                }
            }
        }

        return null;
    }

    public static function preload(Composer $composer): void
    {
        if (is_null(self::$classes)) {
            self::$classes = self::getProjectTypeClasses($composer);
        }
    }

    private static function getProjectTypeClasses(Composer $composer): array
    {
        $classes = [];

        $directories = [];

        foreach ($composer->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {
            $path = $composer->getInstallationManager()->getInstallPath($package);
            if (file_exists("{$path}/.installer/project-types")) {
                $directories[] = "{$path}/.installer/project-types";
            }
        }

        $directories[] = __DIR__;

        try {
            foreach ($directories as $directory) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($directory)
                );

                /** @var SplFileInfo $file */
                foreach ($iterator as $file) {
                    if (!$file->isFile()) {
                        continue;
                    }
                    if ($file->getExtension() !== 'php') {
                        continue;
                    }

                    try {
                        assert(is_string($file->getRealPath()));
                        $reflectionFile = new ReflectionFile($file->getRealPath());
                        if ($reflectionFile->containsClass()) {
                            require_once $file->getRealPath();
                            $reflectionClass = $reflectionFile->getClass();
                            if ($reflectionClass->isInstantiable() && $reflectionClass->implementsInterface(ProjectTypeInterface::class)) {
                                $instance = $reflectionClass->newInstance();
                                assert($instance instanceof ProjectTypeInterface);
                                $classes[$instance->getMachineName()] = $reflectionClass->getName();
                            }
                        }
                    } catch (ReflectionException $e) {
                        // ignore
                    }
                }
            }
        } catch (UnexpectedValueException $exception) {
            $classes = self::$classes;
        }

        return $classes;
    }
}

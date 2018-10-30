<?php

namespace Rikudou\Installer\ProjectType;

use Composer\Composer;
use Rikudou\Installer\Helper\ClassInfoParser;
use Rikudou\Installer\Helper\PreloadInterface;

class ProjectTypeGetter implements PreloadInterface
{

    private static $classes = null;

    /**
     * Returns the project type, either from composer extra section or it tries to detect from file structure.
     * Returns null if no project type is detected.
     *
     * @param Composer $composer
     * @return null|ProjectTypeInterface
     */
    public static function get(Composer $composer): ?ProjectTypeInterface
    {
        $rootDir = dirname($composer->getConfig()->getConfigSource()->getName());

        $classes = static::assignClasses($composer);

        $composerProjectType = $composer->getPackage()->getExtra()["rikudou"]["installer"]["project-type"] ?? null;
        if ($composerProjectType && isset($classes[$composerProjectType])) {
            $class = $classes[$composerProjectType];
            return new $class;
        }

        foreach ($classes as $class) {
            /** @var ProjectTypeInterface $instance */
            $instance = new $class;
            foreach ($instance->getDirs() as $dir) {
                if(is_array($dir)) {
                    $exists = true;
                    foreach ($dir as $requiredDir) {
                        $exists = $exists && file_exists("{$rootDir}/{$requiredDir}");
                    }
                    if($exists) {
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

    private static function assignClasses(Composer $composer): array
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
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($directory)
                );

                /** @var \SplFileInfo $file */
                foreach ($iterator as $file) {
                    if (!$file->isFile()) {
                        continue;
                    }
                    if ($file->getExtension() !== "php") {
                        continue;
                    }

                    $classInfo = new ClassInfoParser($file->getRealPath());

                    if (
                        !$classInfo->isValidClass() ||
                        !$classInfo->isInstantiable() ||
                        !$classInfo->implementsInterface(ProjectTypeInterface::class)
                    ) {
                        continue;
                    }
                    $classes[$classInfo->getReflection()->newInstance()->getMachineName()] = $classInfo->getClassName();
                }
            }
        } catch (\UnexpectedValueException $exception) {
            $classes = self::$classes;
        }

        return $classes;
    }

    public static function preload(Composer $composer): void
    {
        if(is_null(self::$classes)) {
            self::$classes = self::assignClasses($composer);
        }
    }
}
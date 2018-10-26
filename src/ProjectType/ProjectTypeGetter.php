<?php

namespace Rikudou\Installer\ProjectType;

use Composer\Composer;

class ProjectTypeGetter
{

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
                if (file_exists("{$rootDir}/{$dir}")) {
                    return $instance;
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

                $fileReadHandle = fopen($file->getRealPath(), "r");
                $className = "";
                $namespace = "";
                $buffer = "";

                while (!$className) {
                    if (feof($fileReadHandle)) {
                        break;
                    }

                    $buffer .= fread($fileReadHandle, 128);
                    if (strpos($buffer, "class") === false) {
                        continue;
                    }
                    $tokens = @token_get_all($buffer);
                    $tokensCount = count($tokens);

                    for ($i = 0; $i < $tokensCount; $i++) {
                        if ($className) {
                            break;
                        }
                        if (!$namespace && $tokens[$i][0] === T_NAMESPACE) {
                            for ($j = $i + 1; $j < $tokensCount; $j++) {
                                if (
                                    isset($tokens[$j][0]) &&
                                    ($tokens[$j][0] === T_STRING || $tokens[$j][0] === T_NS_SEPARATOR)
                                ) {
                                    if (trim($tokens[$j][0])) {
                                        $namespace .= $tokens[$j][1];
                                    }
                                } else if ($tokens[$j] === '{' || $tokens[$j] === ';') {
                                    break;
                                }
                            }
                        } else if ($tokens[$i][0] === T_CLASS) {
                            for ($j = $i; $j < $tokensCount; $j++) {
                                if (isset($tokens[$j][0]) && $tokens[$j][0] === T_STRING) {
                                    $className = $tokens[$j][1];
                                    break;
                                }
                            }
                        }
                    }
                    if ($namespace && substr($namespace, 0, 1) !== "\\") {
                        $namespace = "\\{$namespace}";
                    }
                    $className = "{$namespace}\\{$className}";
                }

                fclose($fileReadHandle);

                if(!$className) {
                    continue;
                }

                require_once $file->getRealPath();

                if (!class_exists($className)) {
                    continue;
                }

                try {
                    $reflection = new \ReflectionClass($className);
                } catch (\ReflectionException $e) {
                    continue;
                }
                if (
                    !$reflection->isInstantiable() ||
                    !$reflection->implementsInterface(ProjectTypeInterface::class)
                ) {
                    continue;
                }
                $classes[$reflection->newInstance()->getMachineName()] = $reflection->getName();
            }
        }

        return $classes;
    }

}
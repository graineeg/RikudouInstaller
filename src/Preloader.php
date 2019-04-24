<?php

namespace Rikudou\Installer;

use Composer\Composer;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;
use Rikudou\Installer\Helper\PreloadInterface;
use SplFileInfo;

final class Preloader
{
    /**
     * @var Composer
     */
    private $composer;

    public function __construct(Composer $composer)
    {
        $this->composer = $composer;
    }

    public function preload()
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                __DIR__
            )
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                require_once $file->getRealPath();
            }
        }

        $definedClasses = get_declared_classes();
        /** @var string $definedClass */
        foreach ($definedClasses as $definedClass) {
            try {
                $reflection = new ReflectionClass($definedClass);
                if ($reflection->implementsInterface(PreloadInterface::class)) {
                    $callback = [$definedClass, 'preload'];
                    assert(is_callable($callback));
                    call_user_func($callback, $this->composer);
                }
            } catch (ReflectionException $e) {
                continue;
            }
        }
    }
}

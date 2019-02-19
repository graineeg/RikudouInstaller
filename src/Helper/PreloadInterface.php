<?php

namespace Rikudou\Installer\Helper;

use Composer\Composer;

interface PreloadInterface
{
    /**
     * This method is called right at the start of plugin, stuff that needs to present during the whole operations
     * can be defined here (e.g. preloading classes that might get deleted by composer before the operation
     * is done)
     *
     * @param Composer $composer
     */
    public static function preload(Composer $composer): void;
}

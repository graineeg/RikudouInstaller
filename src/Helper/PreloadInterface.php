<?php

namespace Rikudou\Installer\Helper;

use Composer\Composer;

interface PreloadInterface
{

    public static function preload(Composer $composer): void;

}
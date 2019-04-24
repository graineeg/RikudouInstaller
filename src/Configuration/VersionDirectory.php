<?php

namespace Rikudou\Installer\Configuration;

class VersionDirectory
{
    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $path;

    public function __construct(string $version, string $path)
    {
        $this->version = $version;
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
}

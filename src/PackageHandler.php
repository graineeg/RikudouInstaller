<?php

namespace Rikudou\Installer;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Rikudou\Installer\Enums\OperationType;
use Rikudou\Installer\Operations\CopyFilesHandler;
use Rikudou\Installer\Operations\EnvFilesHandler;
use Rikudou\Installer\ProjectType\ProjectTypeInterface;

class PackageHandler
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

    public function __construct(PackageInterface $package, ProjectTypeInterface $projectType, Composer $composer)
    {
        $this->package = $package;
        $this->projectType = $projectType;
        $this->composer = $composer;
    }

    /**
     * Checks whether package contains .installer dir and thus can be handled
     *
     * @return bool
     */
    public function canBeHandled(): bool
    {
        return is_dir("{$this->composer->getInstallationManager()->getInstallPath($this->package)}/.installer");
    }

    /**
     * Checks for supported project type operations and returns true if all operations succeeded,
     * false if any of the operations fails
     *
     * @return bool
     */
    public function handleUninstall(): bool
    {
        $result = true;

        foreach ($this->projectType->getTypes() as $type) {
            switch ($type) {
                case OperationType::COPY_FILES:
                    $result = $result && (new CopyFilesHandler($this->package, $this->projectType, $this->composer))->uninstall();
                    break;
                case OperationType::ENV_FILES:
                    $result = $result && (new EnvFilesHandler($this->package, $this->projectType, $this->composer))->uninstall();
                    break;
            }
        }

        return $result;
    }

    /**
     * Checks for supported project type operations and returns true if all operations succeeded,
     * false if any of the operations fails
     * @return bool
     */
    public function handleInstall(): bool
    {
        $result = true;

        foreach ($this->projectType->getTypes() as $type) {
            switch ($type) {
                case OperationType::COPY_FILES:
                    $result = $result && (new CopyFilesHandler($this->package, $this->projectType, $this->composer))->install();
                    break;
                case OperationType::ENV_FILES:
                    $result = $result && (new EnvFilesHandler($this->package, $this->projectType, $this->composer))->install();
                    break;
            }
        }

        return $result;
    }

}
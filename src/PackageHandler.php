<?php

namespace Rikudou\Installer;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Rikudou\Installer\Operations\OperationHandlerBase;
use Rikudou\Installer\Operations\OperationResult;
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
     * Checks for supported project type operations and returns array of OperationResult instances
     *
     * @return OperationResult[]
     */
    public function handleInstall(): array
    {
        $result = [];

        $handlers = OperationHandlerBase::getHandlers();

        foreach ($this->projectType->getTypes() as $type) {
            if(isset($handlers[$type])) {
                $class = $handlers[$type];
                /** @var OperationHandlerBase $handler */
                $handler = new $class($this->package, $this->projectType, $this->composer);
                $result[] = $handler->install();
            }
        }

        return $result;
    }

    /**
     * Checks for supported project type operations and returns array of OperationResult instances
     *
     * @return OperationResult[]
     */
    public function handleUninstall(): array
    {
        $result = [];

        $handlers = OperationHandlerBase::getHandlers();

        foreach ($this->projectType->getTypes() as $type) {
            if(isset($handlers[$type])) {
                $class = $handlers[$type];
                /** @var OperationHandlerBase $handler */
                $handler = new $class($this->package, $this->projectType, $this->composer);
                $result[] = $handler->uninstall();
            }
        }

        return $result;
    }

}
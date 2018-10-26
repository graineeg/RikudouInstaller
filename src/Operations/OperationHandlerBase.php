<?php

namespace Rikudou\Installer\Operations;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Rikudou\Installer\ProjectType\ProjectTypeInterface;

abstract class OperationHandlerBase
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
     * @return bool
     */
    abstract public function install(): bool;

    /**
     * Handle uninstallation for given operation
     *
     * @return bool
     */
    abstract public function uninstall(): bool;

}
<?php

namespace Rikudou\Installer;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use Rikudou\Installer\ProjectType\ProjectTypeGetter;
use Rikudou\Installer\ProjectType\ProjectTypeInterface;

class Installer implements PluginInterface, EventSubscriberInterface
{

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var ProjectTypeInterface|null
     */
    private $projectType;

    /**
     * @var bool
     */
    private $enabled = true;

    /**
     * @var array
     */
    private $excluded = [];

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     * * The method name to call (priority defaults to 0)
     * * An array composed of the method name to call and the priority
     * * An array of arrays composed of the method names to call and respective
     *   priorities, or 0 if unset
     *
     * For instance:
     *
     * * array('eventName' => 'methodName')
     * * array('eventName' => array('methodName', $priority))
     * * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => ["handleInstall", 1],
            PackageEvents::POST_PACKAGE_UPDATE => ["handleInstall", 1],
            ScriptEvents::PRE_INSTALL_CMD => ["printInfo", 1],
            ScriptEvents::PRE_UPDATE_CMD => ["printInfo", 1],
            PackageEvents::PRE_PACKAGE_UNINSTALL => ["handleUninstall", 1],
        ];
    }

    /**
     * Prints info about the Rikudou installer being present and in use (or being disabled)
     */
    public function printInfo(): void
    {
        if (!$this->enabled) {
            $this->io->write("<info>Rikudou installer disabled in config</info>");
        } else {
            $this->io->write("<info>Rikudou installer enabled</info>");
        }
    }

    /**
     * Apply plugin modifications to Composer
     *
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;

        $this->enabled = $this->composer->getPackage()->getExtra()["rikudou"]["installer"]["enabled"] ?? true;
        $this->excluded = $this->composer->getPackage()->getExtra()['rikudou']['installer']['exclude'] ?? [];
    }

    /**
     * Tries to uninstall the package
     *
     * @param PackageEvent $event
     */
    public function handleUninstall(PackageEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $operation = $event->getOperation();
        if ($operation instanceof UninstallOperation) {
            $package = $operation->getPackage();
            if (in_array($package->getName(), $this->excluded)) {
                $this->io->write(sprintf("<info>Rikudou installer: Package %s ignored in composer settings</info>", $package->getName()));
                return;
            }
            if($package->getName() === "rikudou/installer") {
                $this->enabled = false;
                return;
            }
            $this->projectType = ProjectTypeGetter::get($this->composer);
            $handler = new PackageHandler($package, $this->projectType, $this->composer);
            if ($handler->canBeHandled()) {
                if (!$handler->handleUninstall()) {
                    $this->io->writeError(sprintf("<error>Rikudou installer: Couldn't unconfigure package %s</error>", $package->getName()));
                } else {
                    $this->io->write(sprintf("<info>Rikudou installer: Unconfigured package %s</info>", $package->getName()));
                }
            }
        }
    }

    /**
     * Tries to install the package
     *
     * @param PackageEvent $event
     */
    public function handleInstall(PackageEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        // reassign project type, new project types could have been installed
        $this->projectType = ProjectTypeGetter::get($this->composer);

        if (is_null($this->projectType)) {
            return;
        }

        $operation = $event->getOperation();
        if ($operation instanceof InstallOperation) {
            $package = $operation->getPackage();
        } else if ($operation instanceof UpdateOperation) {
            $package = $operation->getTargetPackage();
        } else {
            return;
        }

        if (in_array($package->getName(), $this->excluded)) {
            $this->io->write(sprintf("<info>Rikudou installer: Package %s ignored in composer settings</info>", $package->getName()));
            return;
        }

        $handler = new PackageHandler($package, $this->projectType, $this->composer);
        if (!$handler->canBeHandled()) {
            return;
        }
        if (!$handler->handleInstall()) {
            $this->io->writeError(sprintf("<error>Rikudou installer: Couldn't configure package %s</error>", $package->getName()));
        } else {
            $this->io->write(sprintf("<info>Rikudou installer: Configured package %s</info>", $package->getName()));
        }
    }

}
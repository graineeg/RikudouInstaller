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
use Rikudou\Installer\Helper\PreloadInterface;
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

        $this->preload();
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
            $this->projectType = ProjectTypeGetter::get($this->composer);
            $handler = new PackageHandler($package, $this->projectType, $this->composer);
            if ($handler->canBeHandled()) {
                $this->io->write("<comment>=== [Rikudou Installer] ===</comment>");
                foreach ($handler->handleUninstall() as $operationResult) {
                    foreach ($operationResult->getMessagesCollection()->getGenerator() as $message) {
                        if ($message->isStatusMessage() || $message->isWarningMessage()) {
                            $this->io->write($message->getMessage());
                        } else {
                            $this->io->writeError($message->getMessage());
                        }
                    }
                }
                $this->io->write("<comment>=== [Rikudou Installer] ===</comment>");
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
        $this->io->write("<comment>=== [Rikudou Installer] ===</comment>");
        foreach ($handler->handleInstall() as $operationResult) {
            foreach ($operationResult->getMessagesCollection()->getGenerator() as $message) {
                if ($message->isStatusMessage() || $message->isWarningMessage()) {
                    $this->io->write($message->getMessage());
                } else {
                    $this->io->writeError($message->getMessage());
                }
            }
        }
        $this->io->write("<comment>=== [Rikudou Installer] ===</comment>");
    }

    /**
     * Preloads all classes
     */
    private function preload(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                __DIR__
            )
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === "php") {
                require_once $file->getRealPath();
            }
        }

        $definedClasses = get_declared_classes();
        /** @var string $definedClass */
        foreach ($definedClasses as $definedClass) {
            try {
                $reflection = new \ReflectionClass($definedClass);
                if($reflection->implementsInterface(PreloadInterface::class)) {
                    call_user_func([$definedClass, "preload"], $this->composer);
                }
            } catch (\ReflectionException $e) {
                continue;
            }
        }

    }

}
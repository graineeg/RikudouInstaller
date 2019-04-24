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
use Rikudou\Installer\Configuration\Config;
use Rikudou\Installer\ProjectType\ProjectTypeInterface;
use Rikudou\Installer\ProjectType\ProjectTypeMatcher;

final class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var bool
     */
    private $failed = false;

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
     * @var Config
     */
    private $config;

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
            PackageEvents::POST_PACKAGE_INSTALL => ['handleInstall', 1],
            PackageEvents::POST_PACKAGE_UPDATE => ['handleInstall', 1],
            ScriptEvents::PRE_INSTALL_CMD => ['printInfo', 1],
            ScriptEvents::PRE_UPDATE_CMD => ['printInfo', 1],
            PackageEvents::PRE_PACKAGE_UNINSTALL => ['handleUninstall', 1],
        ];
    }

    /**
     * Prints info about the Rikudou installer being present and in use (or being disabled)
     */
    public function printInfo(): void
    {
        if ($this->failed) {
            return;
        }
        if (!$this->enabled) {
            $this->io->write('<info>Rikudou installer disabled in config</info>');
        } else {
            $this->io->write('<info>Rikudou installer enabled</info>');
        }
    }

    /**
     * Apply plugin modifications to Composer
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;

        $this->enabled = $this->composer->getPackage()->getExtra()['rikudou']['installer']['enabled'] ?? true;
        $this->excluded = $this->composer->getPackage()->getExtra()['rikudou']['installer']['exclude'] ?? [];

        try {
            $this->config = new Config($composer);
        } catch (Exception\ConfigurationException $e) {
            $this->io->writeError($e->getMessage());
            $this->failed = true;

            return;
        }

        (new Preloader($composer))->preload();
    }

    /**
     * Tries to uninstall the package
     *
     * @param PackageEvent $event
     */
    public function handleUninstall(PackageEvent $event): void
    {
        if (!$this->enabled || $this->failed) {
            return;
        }

        $operation = $event->getOperation();
        if ($operation instanceof UninstallOperation) {
            $package = $operation->getPackage();
            if (in_array($package->getName(), $this->excluded)) {
                $this->io->write(sprintf('<info>Rikudou installer: Package %s ignored in composer settings</info>', $package->getName()));

                return;
            }
            $this->projectType = ProjectTypeMatcher::findProjectType($this->composer);
            if (is_null($this->projectType)) {
                return;
            }
            $handler = new PackageHandler($package, $this->projectType, $this->composer, $this->config);
            if ($handler->containsInstallerDirectory()) {
                $operationResults = $handler->handleUninstall();
                if ($operationResults->madeChanges()) {
                    $this->io->write('<comment>=== [Rikudou Installer] ===</comment>');
                    foreach ($operationResults as $operationResult) {
                        foreach ($operationResult->getMessagesCollection()->getGenerator() as $message) {
                            if ($message->isStatusMessage() || $message->isWarningMessage()) {
                                $this->io->write([
                                    '  - ',
                                    "[{$operationResult->getOperationName()}] ",
                                    "[{$operationResult->getVersion()}] ",
                                    $message->getMessage(),
                                ], false);
                                $this->io->write('');
                            } else {
                                $this->io->writeError([
                                    '  - ',
                                    "[{$operationResult->getOperationName()}] ",
                                    "[{$operationResult->getVersion()}] ",
                                    $message->getMessage(),
                                ], false);
                                $this->io->writeError('');
                            }
                        }
                    }
                    $this->config->flush();
                    $this->io->write('<comment>=== [Rikudou Installer] ===</comment>');
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
        if (!$this->enabled || $this->failed) {
            return;
        }

        // reassign project type, new project types could have been installed
        $this->projectType = ProjectTypeMatcher::findProjectType($this->composer);

        if (is_null($this->projectType)) {
            return;
        }

        $operation = $event->getOperation();
        if ($operation instanceof InstallOperation) {
            $package = $operation->getPackage();
        } elseif ($operation instanceof UpdateOperation) {
            $package = $operation->getTargetPackage();
        } else {
            return;
        }

        if (in_array($package->getName(), $this->excluded)) {
            $this->io->write(sprintf('<info>Rikudou installer: Package %s ignored in composer settings</info>', $package->getName()));

            return;
        }

        $handler = new PackageHandler($package, $this->projectType, $this->composer, $this->config);
        if (!$handler->containsInstallerDirectory()) {
            return;
        }

        $operationResults = $handler->handleInstall();
        if ($operationResults->madeChanges()) {
            $this->io->write('<comment>=== [Rikudou Installer] ===</comment>');
            foreach ($operationResults as $operationResult) {
                foreach ($operationResult->getMessagesCollection()->getGenerator() as $message) {
                    if ($message->isStatusMessage() || $message->isWarningMessage()) {
                        $this->io->write([
                            '  - ',
                            "[{$operationResult->getOperationName()}] ",
                            "[{$operationResult->getVersion()}] ",
                            $message->getMessage(),
                        ], false);
                        $this->io->write('');
                    } else {
                        $this->io->writeError([
                            '  - ',
                            "[{$operationResult->getOperationName()}] ",
                            "[{$operationResult->getVersion()}] ",
                            $message->getMessage(),
                        ], false);
                        $this->io->writeError('');
                    }
                }
            }
            $this->config->flush();
            $this->io->write('<comment>=== [Rikudou Installer] ===</comment>');
        }
    }
}

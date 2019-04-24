<?php

namespace Rikudou\Installer\ProjectType\Type;

use Rikudou\Installer\Enums\OperationType;
use Rikudou\Installer\ProjectType\ProjectTypeInterface;

class Symfony4ProjectType implements ProjectTypeInterface
{
    /**
     * Returns list of directories the project should contain. If any directory is found, this class
     * is assumed as a valid type
     *
     * @return array
     */
    public function getMatchableFiles(): array
    {
        return [
            'config/packages',
        ];
    }

    /**
     * Returns supported types of operations for current project
     *
     * @see \Rikudou\Installer\Enums\OperationType
     *
     * @return array
     */
    public function getTypes(): array
    {
        return [
            OperationType::COPY_FILES,
            OperationType::ENVIRONMENT_VARIABLES,
            OperationType::REGISTER_SYMFONY_BUNDLE,
        ];
    }

    /**
     * Returns the project friendly name for console output
     *
     * @return string
     */
    public function getFriendlyName(): string
    {
        return 'Symfony 4';
    }

    /**
     * Returns the directory from which the configuration will be handled.
     * These must be the direct subdirectories of .installer
     *
     * @return array
     */
    public function getProjectDirs(): array
    {
        return [
            'symfony4',
            'symfony',
        ];
    }

    /**
     * Returns the machine name for manually setting project type in composer.json
     *
     * @return string
     */
    public function getMachineName(): string
    {
        return 'symfony4';
    }
}

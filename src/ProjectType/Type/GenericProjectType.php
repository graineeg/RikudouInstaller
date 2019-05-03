<?php

namespace Rikudou\Installer\ProjectType\Type;

use Rikudou\Installer\Enums\OperationType;
use Rikudou\Installer\ProjectType\ProjectTypeInterface;

class GenericProjectType implements ProjectTypeInterface
{
    /**
     * Returns the project friendly name for console output
     *
     * @return string
     */
    public function getFriendlyName(): string
    {
        return 'Generic project';
    }

    /**
     * Returns the machine name for manually setting project type in composer.json
     *
     * @return string
     */
    public function getMachineName(): string
    {
        return 'any';
    }

    /**
     * Returns list of directories/files the project should contain. If any item is found, this class
     * is assumed as a valid type
     *
     * @return string[]
     */
    public function getMatchableFiles(): array
    {
        return [
            '.',
        ];
    }

    /**
     * Returns supported types of operations for current project
     *
     * @return string[]
     *
     * @see \Rikudou\Installer\Enums\OperationType
     *
     */
    public function getTypes(): array
    {
        return [
            OperationType::COPY_FILES,
            OperationType::ENVIRONMENT_VARIABLES,
        ];
    }

    /**
     * Returns the directory from which the configuration will be handled.
     * These must be the direct subdirectories of .installer
     *
     * @return string[]
     */
    public function getProjectDirs(): array
    {
        return [
            $this->getMachineName(),
        ];
    }
}
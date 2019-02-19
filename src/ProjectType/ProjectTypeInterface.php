<?php

namespace Rikudou\Installer\ProjectType;

interface ProjectTypeInterface
{
    /**
     * Returns the project friendly name for console output
     *
     * @return string
     */
    public function getFriendlyName(): string;

    /**
     * Returns the machine name for manually setting project type in composer.json
     *
     * @return string
     */
    public function getMachineName(): string;

    /**
     * Returns list of directories the project should contain. If any directory is found, this class
     * is assumed as a valid type
     *
     * @return array
     */
    public function getDirs(): array;

    /**
     * Returns supported types of operations for current project
     *
     * @see \Rikudou\Installer\Enums\OperationType
     *
     * @return array
     */
    public function getTypes(): array;

    /**
     * Returns the directory from which the configuration will be handled.
     * These must be the direct subdirectories of .installer
     *
     * @return array
     */
    public function getProjectDirs(): array;
}

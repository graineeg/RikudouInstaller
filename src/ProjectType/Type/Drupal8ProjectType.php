<?php

namespace Rikudou\Installer\ProjectType\Type;

use Rikudou\Installer\Enums\OperationType;
use Rikudou\Installer\ProjectType\ProjectTypeInterface;

final class Drupal8ProjectType implements ProjectTypeInterface
{
    /**
     * Returns the project friendly name for console output
     *
     * @return string
     */
    public function getFriendlyName(): string
    {
        return 'Drupal 8';
    }

    /**
     * Returns the machine name for manually setting project type in composer.json
     *
     * @return string
     */
    public function getMachineName(): string
    {
        return 'drupal-8';
    }

    /**
     * Returns list of directories the project should contain. If any directory is found, this class
     * is assumed as a valid type
     *
     * @return array
     */
    public function getMatchableFiles(): array
    {
        return [
            'core/lib/Drupal',
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
        ];
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
            'drupal',
            'drupal8',
        ];
    }

    /**
     * Sets the priority for this project type, matcher will try to match projects in order.
     *
     * Higher priority means that matcher will try this project type sooner.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 0;
    }
}

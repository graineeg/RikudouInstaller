<?php

namespace Rikudou\Installer\Helper;

interface SupportedProjectTypesInterface
{
    /**
     * Returns the available project types for the current operation
     *
     * @return string[]
     */
    public function getSupportedProjectTypes(): array;
}

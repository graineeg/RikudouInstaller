<?php

namespace Rikudou\Installer\Helper;

interface AvailableOperationInterface
{
    /**
     * Returns true if the operation is available for the current config, e.g. required files exist
     * in the .installer directory
     *
     * @return bool
     */
    public function isAvailable(): bool;
}

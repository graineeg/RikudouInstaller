<?php

namespace Rikudou\Installer\Helper;

interface AvailabilityAwareOperationInterface
{
    /**
     * Returns true if the operation is available for the current config, e.g. required files exist
     * in the .installer directory
     *
     * @param array $paths
     *
     * @return bool
     */
    public function isAvailable(array $paths): bool;
}

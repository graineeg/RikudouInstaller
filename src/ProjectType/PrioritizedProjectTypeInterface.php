<?php


namespace Rikudou\Installer\ProjectType;


interface PrioritizedProjectTypeInterface extends ProjectTypeInterface
{
    /**
     * Sets the priority for this project type, matcher will try to match projects in order.
     *
     * Higher priority means that matcher will try this project type sooner.
     *
     * @return int
     */
    public function getPriority(): int;
}

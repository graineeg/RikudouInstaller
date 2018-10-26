<?php

namespace Rikudou\Installer;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Rikudou\Installer\Enums\OperationType;
use Rikudou\Installer\ProjectType\ProjectTypeInterface;

class PackageHandler
{

    /**
     * @var PackageInterface
     */
    private $package;

    /**
     * @var ProjectTypeInterface
     */
    private $projectType;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var string
     */
    private $projectRootDir;

    /**
     * @var string
     */
    private $packageInstallDir;

    public function __construct(PackageInterface $package, ProjectTypeInterface $projectType, Composer $composer)
    {
        $this->package = $package;
        $this->projectType = $projectType;
        $this->composer = $composer;

        $this->projectRootDir = dirname($this->composer->getConfig()->getConfigSource()->getName());
        $this->packageInstallDir = $this->composer->getInstallationManager()->getInstallPath($this->package);
    }

    /**
     * Checks whether package contains .installer dir and thus can be handled
     *
     * @return bool
     */
    public function canBeHandled(): bool
    {
        return is_dir("{$this->packageInstallDir}/.installer");
    }

    /**
     * Checks for supported project type operations and returns true if all operations succeeded,
     * false if any of the operations fails
     *
     * @return bool
     */
    public function handleUninstall(): bool
    {
        $result = true;

        foreach ($this->projectType->getTypes() as $type) {
            switch ($type) {
                case OperationType::COPY_FILES:
                    $result = $result && $this->handleCopyUninstall();
                    break;
            }
        }

        return $result;
    }

    /**
     * Tries to remove all files that were automatically installed.
     *  - removes empty directories
     *  - removes installed files if their content is identical to the fresh new file
     *  - returns false if the removal of identical file could not be removed
     *  - uses sha1 for checksum
     *
     * @return bool
     */
    public function handleCopyUninstall(): bool
    {
        $failed = false;

        $sourcePath = "{$this->packageInstallDir}/.installer/%s/files";
        foreach ($this->projectType->getProjectDirs() as $projectDir) {
            $directory = sprintf($sourcePath, $projectDir);
            if (is_dir($directory)) {
                /** @var \RecursiveIteratorIterator|\RecursiveDirectoryIterator $iteratorPackage */
                $iteratorPackage = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator(
                        $directory, \RecursiveDirectoryIterator::SKIP_DOTS
                    ),
                    \RecursiveIteratorIterator::CHILD_FIRST
                );

                /** @var \SplFileInfo $file */
                foreach ($iteratorPackage as $file) {
                    $target = "{$this->projectRootDir}/{$iteratorPackage->getSubPathname()}";
                    if (!file_exists($target)) {
                        continue;
                    }
                    if ($file->isDir()) {
                        @rmdir($target);
                    } else {
                        $hashTarget = hash_file("sha1", $target);
                        $hashSource = hash_file("sha1", $file->getRealPath());
                        if ($hashSource === $hashTarget) {
                            if (!@unlink($target)) {
                                $failed = true;
                            }
                        }
                    }
                }
            }
        }

        return !$failed;
    }

    /**
     * Checks for supported project type operations and returns true if all operations succeeded,
     * false if any of the operations fails
     * @return bool
     */
    public function handleInstall(): bool
    {
        $result = true;

        foreach ($this->projectType->getTypes() as $type) {
            switch ($type) {
                case OperationType::COPY_FILES:
                    $result = $result && $this->handleCopyInstall();
                    break;
            }
        }

        return $result;
    }

    /**
     * Copies all files from package to target directory in project root
     *  - files that already exist are not copied
     *  - returns false if a file could not be copied or if a directory could not be created
     * @return bool
     */
    private function handleCopyInstall(): bool
    {
        $failed = false;

        $sourcePath = "{$this->packageInstallDir}/.installer/%s/files";
        foreach ($this->projectType->getProjectDirs() as $projectDir) {
            if (is_dir(sprintf($sourcePath, $projectDir))) {
                /** @var \RecursiveIteratorIterator|\RecursiveDirectoryIterator $iterator */
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator(
                        sprintf($sourcePath, $projectDir), \RecursiveDirectoryIterator::SKIP_DOTS
                    ),
                    \RecursiveIteratorIterator::SELF_FIRST
                );

                /** @var \SplFileInfo $file */
                foreach ($iterator as $file) {
                    $target = "{$this->projectRootDir}/{$iterator->getSubPathname()}";
                    if ($file->isDir()) {
                        if (!is_dir($target)) {
                            if (!mkdir($target)) {
                                $failed = true;
                            }
                        }
                    } else if (!file_exists($target)) {
                        if (!copy($file->getRealPath(), $target)) {
                            $failed = true;
                        }
                    }
                }
            }
        }
        return !$failed;
    }

}
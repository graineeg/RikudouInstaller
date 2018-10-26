<?php

namespace Rikudou\Installer\Operations;

use Rikudou\Installer\Enums\OperationType;

class CopyFilesHandler extends OperationHandlerBase
{

    /**
     * Copies all files from package to target directory in project root
     *  - files that already exist are not copied
     *  - returns false if a file could not be copied or if a directory could not be created
     * @return bool
     */
    public function install(): bool
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

    /**
     * Tries to remove all files that were automatically installed.
     *  - removes empty directories
     *  - removes installed files if their content is identical to the fresh new file
     *  - returns false if the removal of identical file could not be removed
     *  - uses sha1 for checksum
     *
     * @return bool
     */
    public function uninstall(): bool
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
     * Returns the type of operation this class can handle
     *
     * @return string
     */
    public function handles(): string
    {
        return OperationType::COPY_FILES;
    }
}
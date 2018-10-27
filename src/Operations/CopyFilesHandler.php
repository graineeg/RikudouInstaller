<?php

namespace Rikudou\Installer\Operations;

use Rikudou\Installer\Enums\OperationType;

class CopyFilesHandler extends OperationHandlerBase
{

    /**
     * Copies all files from package to target directory in project root
     *  - files that already exist are not copied
     *
     * @return OperationResult
     */
    public function install(): OperationResult
    {
        $exists = false;
        $result = new OperationResult();

        $sourcePath = "{$this->packageInstallDir}/.installer/%s/files";
        foreach ($this->projectType->getProjectDirs() as $projectDir) {
            if (is_dir(sprintf($sourcePath, $projectDir))) {
                $exists = true;
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
                                $result->addErrorMessage("<error>Could not create target directory '$target'</error>");
                            }
                        }
                    } else if (!file_exists($target)) {
                        if (!copy($file->getRealPath(), $target)) {
                            $result->addErrorMessage("<error>Could not copy file to '$target'</error>");
                        }
                    }
                }
            }
        }

        if (!$result->isFailure() && $exists) {
            $result->addStatusMessage("Successfully copied files from package {$this->packageName}");
        }

        return $result;
    }

    /**
     * Tries to remove all files that were automatically installed.
     *  - removes empty directories
     *  - removes installed files if their content is identical to the fresh new file
     *  - uses sha1 for checksum
     *
     * @return OperationResult
     */
    public function uninstall(): OperationResult
    {
        $exists = false;
        $result = new OperationResult();

        $sourcePath = "{$this->packageInstallDir}/.installer/%s/files";
        foreach ($this->projectType->getProjectDirs() as $projectDir) {
            $directory = sprintf($sourcePath, $projectDir);
            if (is_dir($directory)) {
                $exists = true;
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
                                $result->addErrorMessage("<error>Could not delete file '$target'</error>");
                            }
                        }
                    }
                }
            }
        }

        if(!$result->isFailure() && $exists) {
            $result->addStatusMessage("Successfully uninstalled files for {$this->packageName}");
        }

        return $result;
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
<?php

namespace Rikudou\Installer\Operations;

use Rikudou\Installer\Enums\OperationType;
use Rikudou\Installer\Helper\AvailableOperationInterface;
use Rikudou\Installer\Result\OperationResult;

class CopyFilesOperation extends AbstractOperation implements AvailableOperationInterface
{
    /**
     * Copies all files from package to target directory in project root
     *  - files that already exist are not copied
     *
     * @return OperationResult
     */
    public function install(): OperationResult
    {
        $result = new OperationResult();

        $sourcePath = "{$this->packageInstallDir}/.installer/%s/files";
        foreach ($this->projectType->getProjectDirs() as $projectDir) {
            if (is_dir(sprintf($sourcePath, $projectDir))) {
                /** @var \RecursiveDirectoryIterator $iterator */
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator(
                        sprintf($sourcePath, $projectDir),
                        \RecursiveDirectoryIterator::SKIP_DOTS
                    ),
                    \RecursiveIteratorIterator::SELF_FIRST
                );

                /** @var \SplFileInfo $file */
                foreach ($iterator as $file) {
                    $target = "{$this->projectRootDir}/{$iterator->getSubPathname()}";
                    if ($file->isDir()) {
                        if (!is_dir($target)) {
                            if (!mkdir($target)) {
                                $result->addErrorMessage("<error>Could not create target directory '{$target}'</error>");
                            }
                        }
                    } elseif (!file_exists($target)) {
                        assert(is_string($file->getRealPath()));
                        if (!copy($file->getRealPath(), $target)) {
                            $result->addErrorMessage("<error>Could not copy file to '{$target}'</error>");
                        }
                    }
                }
            }
        }

        if (!$result->isFailure()) {
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
        $result = new OperationResult();

        $sourcePath = "{$this->packageInstallDir}/.installer/%s/files";
        foreach ($this->projectType->getProjectDirs() as $projectDir) {
            $directory = sprintf($sourcePath, $projectDir);
            if (is_dir($directory)) {
                /** @var \RecursiveDirectoryIterator $iteratorPackage */
                $iteratorPackage = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator(
                        $directory,
                        \RecursiveDirectoryIterator::SKIP_DOTS
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
                        assert(is_string($file->getRealPath()));
                        $hashTarget = hash_file('sha1', $target);
                        $hashSource = hash_file('sha1', $file->getRealPath());
                        if ($hashSource === $hashTarget) {
                            if (!@unlink($target)) {
                                $result->addErrorMessage("<error>Could not delete file '{$target}'</error>");
                            }
                        }
                    }
                }
            }
        }

        if (!$result->isFailure()) {
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

    /**
     * Returns true if the operation is available for the current config, e.g. required files exist
     * in the .installer directory
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        $sourcePath = "{$this->packageInstallDir}/.installer/%s/files";
        foreach ($this->projectType->getProjectDirs() as $projectDir) {
            if (is_dir(sprintf($sourcePath, $projectDir))) {
                return true;
            }
        }

        return false;
    }
}

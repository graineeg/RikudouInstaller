<?php

namespace Rikudou\Installer\Operations;

use Rikudou\Installer\Enums\OperationType;
use Rikudou\Installer\Helper\AvailabilityAwareOperationInterface;
use Rikudou\Installer\Result\OperationResult;

class GitignoreOperation extends AbstractOperation implements AvailabilityAwareOperationInterface
{
    /**
     * Handle installation for given operation
     *
     * @param string $path The path to the installation directory
     *
     * @return OperationResult
     */
    public function install(string $path): OperationResult
    {
        $result = new OperationResult();

        $hash = uniqid('', true);
        $gitignoreFile = "{$path}/gitignore";

        if (is_file($gitignoreFile)) {
            $content = "\n";
            $content .= "###BEGIN-Rikudou-Installer-{$this->packageName}-{$hash}###\n";
            $content .= "# Do not remove the above line if you want the installer to be able to delete the content on uninstall\n";
            $content .= file_get_contents($gitignoreFile) . "\n";
            $content .= "###END-Rikudou-Installer-{$this->packageName}-{$hash}###";

            $extraConfig = [
                'hash' => $hash,
            ];

            $targetFile = "{$this->projectRootDir}/.gitignore";

            if (!file_exists($targetFile) && !@touch($targetFile)) {
                $result->addErrorMessage('<error>Could not create .gitignore file</error>');

                return $result;
            }

            $handle = fopen($targetFile, 'a');
            if (!fwrite($handle, $content)) {
                $result->addErrorMessage('<error>Could not write to .gitignore file</error>');
            } else {
                $result->addStatusMessage("Successfully copied .gitignore settings from {$this->packageName}");
                $result->setExtraConfig($extraConfig);
            }
            fclose($handle);
        }

        return $result;
    }

    /**
     * Handle uninstallation for given operation
     *
     * @param string $path          The path to the installation directory
     * @param array  $packageConfig The extra config from lock file
     *
     * @return OperationResult
     */
    public function uninstall(string $path, array $packageConfig): OperationResult
    {
        $result = new OperationResult();

        $hash = $packageConfig['hash'] ?? '';
        $file = "{$this->projectRootDir}/.gitignore";
        if (file_exists($file)) {
            $gitignoreContent = file_get_contents($file);
            assert(is_string($gitignoreContent));

            $startString = "\n###BEGIN-Rikudou-Installer-{$this->packageName}-{$hash}###";
            $endString = "###END-Rikudou-Installer-{$this->packageName}-{$hash}###";

            $startPos = strpos($gitignoreContent, $startString);
            if ($startPos === false) {
                return $result;
            }
            $endPos = strpos($gitignoreContent, $endString, $startPos);
            if ($endPos === false) {
                return $result;
            }
            $endPos += strlen($endString);

            $resultingGitignore = substr_replace($gitignoreContent, '', $startPos, $endPos - $startPos);
            if (file_put_contents($file, $resultingGitignore, LOCK_EX) === false) {
                $result->addErrorMessage("Could not uninstall gitignore entries from {$this->packageName}");
            }
        }

        if (!$result->isFailure()) {
            $result->addStatusMessage("Successfully uninstalled gitignore entries from {$this->packageName}");
        }

        return $result;
    }

    /**
     * Returns the type of operation this class can handle.
     * At this method the constructor parameters are not yet available.
     *
     * @return string
     */
    public function handles(): string
    {
        return OperationType::GITIGNORE;
    }

    /**
     * Returns the user friendly name that will be printed to console
     *
     * @return string
     */
    public function getFriendlyName(): string
    {
        return 'Gitignore';
    }

    /**
     * Returns true if the operation is available for the current config, e.g. required files exist
     * in the .installer directory
     *
     * @param array $paths
     *
     * @return bool
     */
    public function isAvailable(array $paths): bool
    {
        foreach ($paths as $path) {
            if (is_file("{$path}/gitignore")) {
                return true;
            }
        }

        return false;
    }
}

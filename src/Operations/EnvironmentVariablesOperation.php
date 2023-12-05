<?php

namespace Rikudou\Installer\Operations;

use Rikudou\Installer\Enums\OperationType;
use Rikudou\Installer\Helper\AvailableOperationInterface;
use Rikudou\Installer\Result\OperationResult;

class EnvironmentVariablesOperation extends AbstractOperation implements AvailableOperationInterface
{
    private const ENV_FILES = [
        '.env.example',
        '.env.local',
        '.env.dist',
        '.env',
    ];

    /**
     * Puts environment variables in .env file if it exists.
     * It looks for files in this order:
     *  - .env.example
     *  - .env.local
     *  - .env.dist
     *  - .env
     * If any of the file exists, the env content is written into it
     *
     * @return OperationResult
     */
    public function install(): OperationResult
    {
        $result = new OperationResult();

        $sourceFile = "{$this->packageInstallDir}/.installer/%s/.env";
        foreach ($this->projectType->getProjectDirs() as $projectDir) {
            $envFile = sprintf($sourceFile, $projectDir);
            if (is_file($envFile)) {
                $content = "\n";
                $content .= "###BEGIN-Rikudou-Installer-{$this->packageName}###\n";
                $content .= "# Do not remove the above line if you want the installer to be able to delete the content on uninstall\n";
                $content .= file_get_contents($envFile) . "\n";
                $content .= "###END-Rikudou-Installer-{$this->packageName}###";

                foreach (self::ENV_FILES as $file) {
                    $targetEnvFile = "{$this->projectRootDir}/{$file}";
                    if (file_exists($targetEnvFile)) {
                        if (!file_put_contents($targetEnvFile, $content, FILE_APPEND | LOCK_EX)) {
                            $result->addErrorMessage("<error>Could not copy env variables from {$this->packageName}</error>");
                        }
                    }
                }
            }
        }

        if (!$result->isFailure()) {
            $result->addStatusMessage("Successfully copied env variables from {$this->packageName}");
        }

        return $result;
    }

    /**
     * Tries to delete defined env variables from these files:
     *  - .env.example
     *  - .env.local
     *  - .env.dist
     *  - .env
     *
     * @return OperationResult
     */
    public function uninstall(): OperationResult
    {
        $result = new OperationResult();

        foreach (self::ENV_FILES as $file) {
            $file = "{$this->projectRootDir}/{$file}";
            if (file_exists($file)) {
                $envContent = file_get_contents($file);
                assert(is_string($envContent));

                $startString = "\n###BEGIN-Rikudou-Installer-{$this->packageName}###";
                $endString = "###END-Rikudou-Installer-{$this->packageName}###";

                $startPos = strpos($envContent, $startString);
                if ($startPos === false) {
                    continue;
                }
                $endPos = strpos($envContent, $endString, $startPos);
                if ($endPos === false) {
                    continue;
                }
                $endPos += strlen($endString);

                $resultEnv = substr_replace($envContent, '', $startPos, $endPos - $startPos);
                if (file_put_contents($file, $resultEnv, LOCK_EX) === false) {
                    $result->addErrorMessage("Could not uninstall env variables from {$this->packageName}");
                }
            }
        }

        if ($result->isSuccess()) {
            $result->addStatusMessage("Successfully uninstalled env variables from {$this->packageName}");
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
        return OperationType::ENVIRONMENT_VARIABLES;
    }

    /**
     * Returns true if the operation is available for the current config, e.g. required files exist
     * in the .installer directory
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        $projectEnvFileExists = false;
        foreach (self::ENV_FILES as $envFile) {
            if (file_exists("{$this->projectRootDir}/{$envFile}")) {
                $projectEnvFileExists = true;
            }
        }

        if (!$projectEnvFileExists) {
            return false;
        }

        $sourceFile = "{$this->packageInstallDir}/.installer/%s/.env";
        foreach ($this->projectType->getProjectDirs() as $projectDir) {
            $envFile = sprintf($sourceFile, $projectDir);
            if (is_file($envFile)) {
                return true;
            }
        }

        return false;
    }
}

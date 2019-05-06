<?php

namespace Rikudou\Installer\Operations;

use Rikudou\Installer\Enums\OperationType;
use Rikudou\Installer\Helper\AvailabilityAwareOperationInterface;
use Rikudou\Installer\Result\OperationResult;

final class EnvironmentVariablesOperation extends AbstractOperation implements AvailabilityAwareOperationInterface
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
     * @param string $path List of directories that contain operation relevant files
     *
     * @return OperationResult
     */
    public function install(string $path): OperationResult
    {
        $result = new OperationResult();

        $hash = uniqid('', true);
        $envFile = "{$path}/.env";
        if (is_file($envFile)) {
            $content = "\n";
            $content .= "###BEGIN-Rikudou-Installer-{$this->packageName}-{$hash}###\n";
            $content .= "# Do not remove the above line if you want the installer to be able to delete the content on uninstall\n";
            $content .= file_get_contents($envFile) . "\n";
            $content .= "###END-Rikudou-Installer-{$this->packageName}-{$hash}###";

            $extraConfig = [
                'hash' => $hash,
                'files' => [],
            ];

            foreach (self::ENV_FILES as $file) {
                $targetEnvFile = "{$this->projectRootDir}/${file}";
                if (file_exists($targetEnvFile)) {
                    if (file_put_contents($targetEnvFile, $content, FILE_APPEND | LOCK_EX) === false) {
                        $result->addErrorMessage("<error>Could not copy env variables from {$this->packageName}</error>");
                    } else {
                        $extraConfig['files'][] = $file;
                    }
                }
            }

            $result->setExtraConfig($extraConfig);

            if (!$result->isFailure()) {
                $result->addStatusMessage("Successfully copied env variables from {$this->packageName}");
            }
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
     * @param string $path
     * @param array  $packageConfig
     *
     * @return OperationResult
     */
    public function uninstall(string $path, array $packageConfig): OperationResult
    {
        $result = new OperationResult();

        $hash = $packageConfig['hash'] ?? '';
        $files = $packageConfig['files'] ?? [];
        foreach ($files as $file) {
            $file = "{$this->projectRootDir}/{$file}";
            if (file_exists($file)) {
                $envContent = file_get_contents($file);
                assert(is_string($envContent));

                $startString = "\n###BEGIN-Rikudou-Installer-{$this->packageName}-{$hash}###";
                $endString = "###END-Rikudou-Installer-{$this->packageName}-{$hash}###";

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

        if (!$result->isFailure()) {
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
     * @param array $paths
     *
     * @return bool
     */
    public function isAvailable(array $paths): bool
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

        foreach ($paths as $projectDir) {
            $envFile = "{$projectDir}/.env";
            if (is_file($envFile)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the user friendly name that will be printed to console
     *
     * @return string
     */
    public function getFriendlyName(): string
    {
        return 'Copy Environment Variables';
    }
}

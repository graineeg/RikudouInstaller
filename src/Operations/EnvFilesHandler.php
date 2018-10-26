<?php

namespace Rikudou\Installer\Operations;

class EnvFilesHandler extends OperationHandlerBase
{

    /**
     * Puts environment variables in .env file if it exists.
     * It looks for files in this order:
     *  - .env.example
     *  - .env.dist
     *  - .env
     * If any of the file exists, the env content is written into it
     *
     * @return bool
     */
    public function install(): bool
    {
        $failed = false;

        $sourceFile = "{$this->packageInstallDir}/.installer/%s/.env";
        foreach ($this->projectType->getProjectDirs() as $projectDir) {
            $envFile = sprintf($sourceFile, $projectDir);
            if (is_file($envFile)) {
                $content = "\n";
                $content .= "###BEGIN-Rikudou-Installer-{$this->packageName}###\n";
                $content .= "# Do not remove the above line if you want the installer to be able to delete the content on uninstall\n";
                $content .= file_get_contents($envFile) . "\n";
                $content .= "###END-Rikudou-Installer-{$this->packageName}###";

                $files = [
                    ".env.example",
                    ".env.dist",
                    ".env"
                ];

                foreach ($files as $file) {
                    $targetEnvFile = "{$this->projectRootDir}/$file";
                    if (file_exists($targetEnvFile)) {
                        if (!file_put_contents($targetEnvFile, $content, FILE_APPEND | LOCK_EX)) {
                            $failed = true;
                        }
                    }
                }
            }
        }
        return !$failed;
    }

    /**
     * Tries to delete defined env variables from these files:
     *  - .env.example
     *  - .env.dist
     *  - .env
     *
     * @return bool
     */
    public function uninstall(): bool
    {
        $failed = false;
        $files = [
            ".env.example",
            ".env.dist",
            ".env"
        ];
        foreach ($files as $file) {
            $file = "{$this->projectRootDir}/{$file}";
            if (file_exists($file)) {
                $envContent = file_get_contents($file);

                $startString = "\n###BEGIN-Rikudou-Installer-{$this->package->getName()}###";
                $endString = "###END-Rikudou-Installer-{$this->package->getName()}###";

                $startPos = strpos($envContent, $startString);
                if ($startPos === false) {
                    continue;
                }
                $endPos = strpos($envContent, $endString, $startPos);
                if ($endPos === false) {
                    continue;
                }
                $endPos += strlen($endString);

                $resultEnv = substr_replace($envContent, "", $startPos, $endPos - $startPos);
                if (!file_put_contents($file, $resultEnv, LOCK_EX)) {
                    $failed = true;
                }
            }
        }
        return !$failed;
    }
}
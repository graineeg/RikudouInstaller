<?php

namespace Rikudou\Installer\Operations;

use Rikudou\Installer\Enums\OperationType;

class BundleRegisterHandler extends OperationHandlerBase
{

    /**
     * Handle installation for given operation
     *
     * @return OperationResult
     */
    public function install(): OperationResult
    {
        $exists = false;
        $result = new OperationResult();

        $sourcePath = "{$this->packageInstallDir}/.installer/%s/bundles.php";
        foreach ($this->projectType->getProjectDirs() as $projectDir) {
            $bundleFile = sprintf($sourcePath, $projectDir);
            if (is_file($bundleFile)) {
                $exists = true;

                $data = require $bundleFile;
                if (!is_array($data)) {
                    $result->addErrorMessage("<error>The bundles.php file must return an array</error>");
                }

                $installedBundlesFile = "{$this->projectRootDir}/config/bundles.php";
                if (file_exists($installedBundlesFile)) {
                    $installedBundles = require $installedBundlesFile;
                    if (is_array($installedBundles)) {
                        $resultingBundleData = array_merge($installedBundles, $data);

                        $export = $this->dumpConfig($resultingBundleData);
                        if (!file_put_contents($installedBundlesFile, $export, LOCK_EX)) {
                            $result->addErrorMessage("<error>Could not copy bundle content from {$this->packageName}</error>");
                        }
                    }
                }
            }
        }

        if (!$result->isFailure() && $exists) {
            $result->addStatusMessage("Successfully copied Bundle information from {$this->packageName}");
        }

        return $result;
    }

    /**
     * Handle uninstallation for given operation
     *
     * @return OperationResult
     */
    public function uninstall(): OperationResult
    {
        $result = new OperationResult();

        $result->addErrorMessage('The Bundle operation currently does not support automatic uninstall, please remove the entries manually from config/bundles.php');

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
        return OperationType::REGISTER_SYMFONY_BUNDLE;
    }

    private function dumpConfig(array $bundles): string
    {
        $content = "<?php\n\nreturn [\n";
        foreach ($bundles as $class => $envs) {
            $content .= "    {$class}::class => [";
            foreach (array_keys($envs) as $env) {
                $content .= "'{$env}' => true, ";
            }
            $content = substr($content, 0, -2);
            $content .= "],\n";
        }
        $content .= "];\n";

        return $content;
    }
}
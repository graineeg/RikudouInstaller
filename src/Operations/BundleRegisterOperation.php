<?php

namespace Rikudou\Installer\Operations;

use Rikudou\Installer\Enums\OperationType;
use Rikudou\Installer\Helper\AvailableOperationInterface;
use Rikudou\Installer\Result\OperationResult;

class BundleRegisterOperation extends AbstractOperation implements AvailableOperationInterface
{
    /**
     * Handle installation for given operation
     *
     * @return OperationResult
     */
    public function install(): OperationResult
    {
        $result = new OperationResult();

        $sourcePath = "{$this->packageInstallDir}/.installer/%s/bundles.php";
        foreach ($this->projectType->getProjectDirs() as $projectDir) {
            $bundleFile = sprintf($sourcePath, $projectDir);
            if (is_file($bundleFile)) {
                $data = require $bundleFile;
                if (!is_array($data)) {
                    $result->addErrorMessage('<error>The bundles.php file in installer must return an array</error>');
                    break;
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

        if (!$result->isFailure()) {
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

        $sourcePath = "{$this->packageInstallDir}/.installer/%s/bundles.php";
        foreach ($this->projectType->getProjectDirs() as $projectDir) {
            $bundleFile = sprintf($sourcePath, $projectDir);
            if (is_file($bundleFile)) {
                $data = require $bundleFile;
                if (!is_array($data)) {
                    $result->addErrorMessage('<error>The bundles.php file in installer must return an array</error>');
                    break;
                }

                $installedBundlesFile = "{$this->projectRootDir}/config/bundles.php";
                if (file_exists($installedBundlesFile)) {
                    $installedBundles = require $installedBundlesFile;
                    if (is_array($installedBundles)) {
                        $export = $this->dumpConfig($installedBundles, array_keys($data));
                        if (!file_put_contents($installedBundlesFile, $export, LOCK_EX)) {
                            $result->addErrorMessage("<error>Could not remove bundle content from {$this->packageName}</error>");
                        }
                    }
                }
            }
        }

        if (!$result->isFailure()) {
            $result->addStatusMessage("Successfully uninstalled Bundle information from {$this->packageName}");
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
        return OperationType::REGISTER_SYMFONY_BUNDLE;
    }

    /**
     * Returns true if the operation is available for the current config, e.g. required files exist
     * in the .installer directory
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        if (!file_exists("{$this->projectRootDir}/config/bundles.php")) {
            return false;
        }
        $sourcePath = "{$this->packageInstallDir}/.installer/%s/bundles.php";
        foreach ($this->projectType->getProjectDirs() as $projectDir) {
            $bundleFile = sprintf($sourcePath, $projectDir);
            if (is_file($bundleFile)) {
                return true;
            }
        }

        return false;
    }

    private function dumpConfig(array $bundles, array $ignoredClasses = []): string
    {
        $content = "<?php\n\nreturn [\n";
        foreach ($bundles as $class => $envs) {
            if (in_array($class, $ignoredClasses)) {
                continue;
            }
            $content .= "    {$class}::class => [";
            foreach ($envs as $env => $value) {
                $value = $value ? 'true' : 'false';
                $content .= "'{$env}' => {$value}, ";
            }
            $content = substr($content, 0, -2);
            $content .= "],\n";
        }
        $content .= "];\n";

        return $content;
    }
}

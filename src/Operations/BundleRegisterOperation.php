<?php

namespace Rikudou\Installer\Operations;

use Rikudou\Installer\Enums\OperationType;
use Rikudou\Installer\Helper\AvailabilityAwareOperationInterface;
use Rikudou\Installer\Result\OperationResult;

final class BundleRegisterOperation extends AbstractOperation implements AvailabilityAwareOperationInterface
{
    /**
     * @param string $path The path to the installation directory
     *
     * @return OperationResult
     */
    public function install(string $path): OperationResult
    {
        $result = new OperationResult();

        $bundleFile = "{$path}/bundles.php";
        if (is_file($bundleFile)) {
            $data = require $bundleFile;
            if (!is_array($data)) {
                $result->addErrorMessage('<error>The bundles.php file in installer must return an array</error>');

                return $result;
            }

            $installedBundlesFile = "{$this->projectRootDir}/config/bundles.php";
            if (file_exists($installedBundlesFile)) {
                $installedBundles = require $installedBundlesFile;
                if (is_array($installedBundles)) {
                    $resultingBundleData = array_merge($installedBundles, $data);

                    $export = $this->dumpConfig($resultingBundleData);
                    if (!file_put_contents($installedBundlesFile, $export, LOCK_EX)) {
                        $result->addErrorMessage("<error>Could not copy bundle content from {$this->packageName}</error>");

                        return $result;
                    }
                }
            }
            $result->setExtraConfig([
                'success' => true,
            ]);
            $result->addStatusMessage("Successfully copied Bundle information from {$this->packageName}");
        }

        return $result;
    }

    /**
     * Handle uninstallation for given operation
     *
     * @param string $path
     * @param array  $packageConfig
     *
     * @return OperationResult
     */
    public function uninstall(string $path, array $packageConfig): OperationResult
    {
        $result = new OperationResult();
        if (!($packageConfig['success'] ?? false)) {
            return $result;
        }

        $bundleFile = "{$path}/bundles.php";
        if (is_file($bundleFile)) {
            $data = require $bundleFile;
            if (!is_array($data)) {
                $result->addErrorMessage('<error>The bundles.php file in installer must return an array</error>');

                return $result;
            }

            $installedBundlesFile = "{$this->projectRootDir}/config/bundles.php";
            if (file_exists($installedBundlesFile)) {
                $installedBundles = require $installedBundlesFile;
                if (is_array($installedBundles)) {
                    $export = $this->dumpConfig($installedBundles, array_keys($data));
                    if (!file_put_contents($installedBundlesFile, $export, LOCK_EX)) {
                        $result->addErrorMessage("<error>Could not remove bundle content from {$this->packageName}</error>");

                        return $result;
                    }
                }
            }
        }

        $result->addStatusMessage("Successfully uninstalled Bundle information from {$this->packageName}");

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
     * @param array $paths
     *
     * @return bool
     */
    public function isAvailable(array $paths): bool
    {
        if (!file_exists("{$this->projectRootDir}/config/bundles.php")) {
            return false;
        }
        foreach ($paths as $path) {
            if (is_file("{$path}/bundles.php")) {
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
        return 'Register Bundle';
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

<?php

namespace LaravelRequire\Support\Packages;

use LaravelRequire\Exceptions\ServiceProviderAlreadyRegisteredException;
use LaravelRequire\Exceptions\ServiceProvidersVariableMissingException;
use LaravelRequire\Support\RegisteredItemInformation;
use LaravelRequire\Support\ProjectConfiguration;
use LaravelRequire\Support\ClassInformationParser;

class LaravelPackage
{

   /**
    * @var string
    */
    public $name;

   /**
    * @var string
    */
    public $path;

    /**
     * The core method of the `require:package` artisan command.
     * This registers the provided
     * item, which is either a service provider or a facade.
     *
     * @param RegisteredItemInformation $item
     * @param string $thisBaseNamespace
     * @throws ServiceProviderAlreadyRegisteredException
     * @throws ServiceProvidersVariableMissingException
     * @return boolean
     */
    public function registerPackageItem(RegisteredItemInformation $item, $thisBaseNamespace)
    {
        $projectConfig = new ProjectConfiguration();
        $regline = $this->generateRegistrationLine($item);
        $config = ($projectConfig)->readConfigurationFile();
        $parser = new ClassInformationParser();

        if (strpos($config, $regline) !== false) {
            throw new ServiceProviderAlreadyRegisteredException($item->displayName() . ' ' . $item->qualifiedName() . " is already registered.");
        }

        if ($item->type == RegisteredItemInformation::SERVICE_PROVIDER_TYPE) {
            // search for our package's registration (laravel-require), so we
            // know where to insert the new package registration
            $thisBaseNamespace = $parser->getTopLevelNamespace($thisBaseNamespace);

            $thisServiceProviderLine = "$thisBaseNamespace\\${thisBaseNamespace}ServiceProvider::class,";
            $searchLine = $thisServiceProviderLine;

            if (strpos($config, $thisServiceProviderLine) === false) {
                throw new ServiceProvidersVariableMissingException("Could not find registration for the $thisBaseNamespace package in config/app.php.  " . "Please add it to the end of the service providers array to use this command.");
            }
        }

        if ($item->type == RegisteredItemInformation::FACADE_TYPE) {
            $searchLine = "'aliases' => [";
            // some Facades provided by packages are named 'Facade.php', so we
            // will try
            // to guess the correct Facade name based on its namespace;
            if (strtolower($item->classname) == 'facade') {
                $item->name = $parser->getNamespaceSection($item->namespace, 2);
                // regenerate the registration line with the updated name
                $regline = $this->generateRegistrationLine($item);
            }
        }

        $count = 0;
        $config = str_replace($searchLine, $searchLine . PHP_EOL . "        $regline" . PHP_EOL, $config, $count);

        if ($count > 0) {
            $projectConfig->writeConfigurationFile($config);
            return true;
        }

        return false;
    }

    /**
     * Generate the code needed to register the service provider.
     *
     * @param RegisteredItemInformation $item
     * @return string
     */
    protected function generateRegistrationLine(RegisteredItemInformation $item)
    {
        switch ($item->type) {
            case RegisteredItemInformation::SERVICE_PROVIDER_TYPE:
                return $item->namespace . "\\" . $item->classname . "::class,";

            case RegisteredItemInformation::FACADE_TYPE:
                return "'" . (strlen($item->name) > 0 ? $item->name : $item->classname) . "' => " . $item->namespace . '\\' . $item->classname . "::class,";

            default:
                return '';
        }
    }

}
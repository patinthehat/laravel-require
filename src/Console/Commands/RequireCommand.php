<?php

namespace LaravelRequire\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use LaravelRequire\Exceptions\InvalidPackageNameException;
use LaravelRequire\Exceptions\ServiceProviderAlreadyRegisteredException;
use LaravelRequire\Exceptions\ServiceProvidersVariableMissingException;
use LaravelRequire\Support\ServiceProviderInformation;
use LaravelRequire\Support\FilenameFilter;
use LaravelRequire\Support\PackageFilesFilterIterator;
use LaravelRequire\Support\PackageFileLocator;
use LaravelRequire\Support\PackageFileScanner;
use LaravelRequire\Support\Rules\ServiceProviderRule;
use LaravelRequire\Support\Rules\FacadeRule;
use LaravelRequire\Support\Rules\MatchFilenameRuleContract;
use LaravelRequire\Support\RegisteredItemInformation;
use LaravelRequire\Support\ClassInformationParser;


class RequireCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'require:package {package} {--register-only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install a Laravel package with composer and automatically register its service providers and facades';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $packageName = $this->argument('package');
        $registerOnly = $this->option('register-only');

        try {
            $this->validatePackageName($packageName);
        } catch(InvalidPackageNameException $e) {
            $this->comment($e->getMessage());
            return;
        }

        if (!$registerOnly) {
            $composerRequireCommand = $this->findComposerBinary() . " require $packageName";
            $process = new Process($composerRequireCommand, base_path(), null, null, null);

            if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
                $process->setTty(true);
            }

            $this->comment("> composer require $packageName...");

            $process->run(function ($type, $line) {
                $this->line($line);
            });
        }

        $splist = (array)$this->findPackageFilesToRegister($packageName);
        $providers = [];

        //found multiple service providers
        if (count($splist) > 1) {

            $selected = $this->choice(
                            "Multiple Service Provider and/or Facade classes were located.\n".
                            " Please enter a comma-separated list of item numbers to register. Default:",
                            $splist, 0, null, true
            );

            foreach($selected as $class) {
                foreach($splist as $info) {
                    if ($info->qualifiedName() == $class) {
                        $providers[] = $info;
                    }
                }
            }

        } else {
            $providers = $splist; //Didn't find more than one service provider/facade
        }

        foreach($providers as $provider) {

             if (!$provider->filename) {
                $this->comment(
                    "The service provider file for ".$provider->qualifiedName()." could not be found.  ".
                    "This package must be registered manually."
                );
                continue;
            }

            try {
                $this->info("Registering ".$provider->displayName().': '.$provider->qualifiedName()."...");

                if ($this->registerPackageItem($provider)) {
                    $this->info('...registered successfully.');
                } else {
                    $this->comment('The package and/or service provider did not register or install correctly.');
                }
            } catch (ServiceProvidersVariableMissingException $e) {
                $this->comment($e->getMessage());
            } catch (ServiceProviderAlreadyRegisteredException $e) {
                $this->comment($e->getMessage());
            }
        } //end foreach(providers)
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposerBinary()
    {
        if (file_exists(base_path().'/composer.phar')) {
            return '"'.PHP_BINARY.'" composer.phar';
        }

        return 'composer';
    }

    protected function validatePackageName($packageName)
    {
        if (preg_match('/[a-zA-Z0-9_\-]+\/[a-zA-Z0-9_\-]+/', $packageName)==0)
            throw new InvalidPackageNameException("Invalid package name provided: $packageName");
    }

    protected function findPackageFilesToRegister($packageName)
    {
        $locator = new PackageFileLocator($packageName);
        $scanner = new PackageFileScanner();
        $files   = $locator->locatePackageFiles();

        foreach ($files as $file) {
            $spInfo = $scanner->scanFile($file->getPathname(), new ServiceProviderRule);
            $fInfo  = $scanner->scanFile($file->getPathname(), new FacadeRule);
            if ($fInfo !== false)
                $result[] = $fInfo->type(RegisteredItemInformation::FACADE_TYPE);
            if ($spInfo !== false)
                $result[] = $spInfo->type(RegisteredItemInformation::SERVICE_PROVIDER_TYPE);
        }

        return $result;
    }

    protected function generateRegistrationLine(RegisteredItemInformation $item)
    {
        switch ($item->type) {
            case RegisteredItemInformation::SERVICE_PROVIDER_TYPE:
                return $item->namespace."\\".$item->classname."::class,";

            case RegisteredItemInformation::FACADE_TYPE:
                return "'".(strlen($item->name)>0?$item->name:$item->classname)."' => ".$item->namespace.'\\'.$item->classname."::class,";

            default:
                return '';
        }
    }

    protected function readConfigurationFile()
    {
        return file_get_contents(config_path() . '/app.php');
    }

    protected function writeConfigurationFile($contents)
    {
        return file_put_contents(config_path() . '/app.php', $contents);
    }

    protected function registerPackageItem(RegisteredItemInformation $item)
    {
        $regline = $this->generateRegistrationLine($item);
        $config  = $this->readConfigurationFile();
        $parser  = new ClassInformationParser();

        if (strpos($config, $regline) !== false) {
            throw new ServiceProviderAlreadyRegisteredException($item->displayName().' '.$item->qualifiedName()." is already registered.");
        }

        if ($item->type == RegisteredItemInformation::SERVICE_PROVIDER_TYPE) {
            //search for our package's registration (laravel-require), so we know where to insert the new package registration
            $thisBaseNamespace = $parser->getTopLevelNamespace(__NAMESPACE__);
            $thisServiceProviderLine = "$thisBaseNamespace\\${thisBaseNamespace}ServiceProvider::class,";
            $searchLine = $thisServiceProviderLine;

            if (strpos($config, $thisServiceProviderLine) === false) {
                throw new ServiceProvidersVariableMissingException(
                    "Could not find registration for the $thisBaseNamespace package in config/app.php.  ".
                    "Please add it to the end of the service providers array to use this command."
                );
            }
        }

        if ($item->type == RegisteredItemInformation::FACADE_TYPE) {
            $searchLine = "'aliases' => [";
            //some Facades provided by packages are named 'Facade.php', so we will try
            //to guess the correct Facade name based on its namespace;
            if (strtolower($item->classname) == 'facade') {
                $item->name = $parser->getNamespaceSection($item->namespace, 2);
                //regenerate the registration line with the updated name
                $regline = $this->generateRegistrationLine($item);
            }

        }

        $count = 0;
        $config = str_replace(
                    $searchLine,
                    $searchLine . PHP_EOL .
                    "        $regline".PHP_EOL,
                    $config, $count
        );

        if ($count > 0) {
            $this->writeConfigurationFile($config);
            return true;
        }

        return false;
    }
}

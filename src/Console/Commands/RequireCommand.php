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

class RequireCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'require:package {package} {--scan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install a Laravel package with composer and automatically register its service provider';

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
        $scanFileContents = $this->hasOption('scan');

        $testing = $this->hasOption('test') && $this->option('test') == '1';

        try {
            $this->validatePackageName($packageName);
        } catch(InvalidPackageNameException $e) {
            $this->comment($e->getMessage());
            return;
        }


        $composerRequireCommand = $this->findComposerBinary() . " require $packageName";
        $process = new Process($composerRequireCommand, base_path(), null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            $process->setTty(true);
        }

        $this->comment("> composer require $packageName...");

        $process->run(function ($type, $line) {
            $this->line($line);
        });


        $splist = (array)$this->findPackageServiceProviderFile($packageName, $scanFileContents);
        $providers = [];

        //found multiple service providers
        if (count($splist) > 1) {

            $selected = $this->choice(
                            "Multiple Service Provider classes were found.\n".
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
            //Didn't find more than one service provider
            $providers = $splist;
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
                $this->info("Registering Service Provider: ".$provider->qualifiedName()."...");

                if ($this->installServiceProvider($provider)) {
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

    protected function getPackagePath($packageName)
    {
        return base_path() . "/vendor/".strtolower($packageName);
    }

    protected function findPackageServiceProviderFile($packageName, $scanFileContents)
    {
        $fileiterator   = new \RecursiveDirectoryIterator(
                                    $this->getPackagePath($packageName),
                                    \FilesystemIterator::KEY_AS_PATHNAME |
                                    \FilesystemIterator::CURRENT_AS_FILEINFO |
                                    \FilesystemIterator::SKIP_DOTS |
                                    \FilesystemIterator::FOLLOW_SYMLINKS
                                );
        $iterator       = new \RecursiveIteratorIterator(
                                    new PackageFilesFilterIterator($fileiterator),
                                    \RecursiveIteratorIterator::SELF_FIRST
                                );
        $result = [];

        foreach ($iterator as $file) {
            $base = $file->getBasename();
            $isPhpFile = $file->getExtension() == 'php';

            if ($scanFileContents && $isPhpFile) {

                $contents = file_get_contents($file);
                $foundSerivceProviderClass = (preg_match('/\b([a-zA-Z0-9_]+)\s+extends\s+([a-zA-Z0-9_\\\]*ServiceProvider)\b/', $contents, $m)==1);

                if ($foundSerivceProviderClass) {
                    $info = new ServiceProviderInformation();
                    $info->classname($m[1])
                        ->filename($file)
                        ->extends($m[2]);

                    $namespace  = $this->extractNamespaceFromSource($contents);
                    $info->namespace($namespace);
                    $result[] = $info;
                }
            }

            if (!$scanFileContents && $isPhpFile && preg_match('/^[a-zA-Z0-9_]*ServiceProvider\.php$/',$base)==1) {
                $info = new ServiceProviderInformation();
                $data = file_get_contents($file);

                $classname  = $this->extractClassnameFromSource($data);
                $namespace  = $this->extractNamespaceFromSource($data);

                $result[] = $info->filename($file)
                                ->classname($classname)
                                ->namespace($namespace)
                                ->extends('');
            }
        }

        return $result;
    }

    protected function extractClassnameFromSource($code)
    {
        if (preg_match('/\bclass[\s\r\n]*([a-zA-Z0-9_]+)\b/', $code, $m)==1) {
            return $m[1];
        }

        return false;
    }

    protected function extractNamespaceFromSource($code)
    {
        if (preg_match('/namespace\s*([^;]+);/', $code, $m)==1) {
            return trim($m[1]);
        }

        return false;
    }

    protected function generateServiceProviderRegistrationLine(ServiceProviderInformation $provider)
    {
        return $provider->namespace."\\".$provider->classname."::class,";
    }

    protected function extractNamespacePart($namespace, $part = 1)
    {
        $parts = explode("\\", "$namespace\\");
        return $parts[max([0,$part - 1])];
    }

    protected function readConfigurationFile()
    {
        return file_get_contents(config_path() . '/app.php');
    }

    protected function writeConfigurationFile($contents)
    {
        return file_put_contents(config_path() . '/app.php', $contents);
    }

    protected function installServiceProvider(ServiceProviderInformation $provider)
    {
        $regline = $this->generateServiceProviderRegistrationLine($provider);
        $config  = $this->readConfigurationFile();

        if (strpos($config, $regline) !== false) {
            throw new ServiceProviderAlreadyRegisteredException("Service provider ".$provider->qualifiedName()." is already registered.");
        }

        //search for our package's registration (laravel-require), so we know where to insert the new package registration
        $thisBaseNamespace = $this->extractNamespacePart(__NAMESPACE__, 1);
        $thisServiceProviderLine = "$thisBaseNamespace\\${thisBaseNamespace}ServiceProvider::class,";

        if (strpos($config, $thisServiceProviderLine) === false) {
            throw new ServiceProvidersVariableMissingException(
                "Could not find registration for the $thisBaseNamespace package in config/app.php.  ".
                "Please add it to the end of the service providers array to use this command."
            );
        }

        $count = 0;
        $config = str_replace($thisServiceProviderLine, $thisServiceProviderLine . PHP_EOL . "        $regline".PHP_EOL, $config, $count);

        if ($count > 0) {
            $this->writeConfigurationFile($config);
            return true;
        }

        return false;
    }
}

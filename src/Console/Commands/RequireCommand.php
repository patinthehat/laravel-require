<?php

namespace LaravelRequire\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class RequireCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'require:package {package}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically install a Laravel package using composer and then register its service provider';

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

        $composerRequireCommand = $this->findComposerBinary() . " require $packageName";
        $process = new Process($composerRequireCommand, base_path(), null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            $process->setTty(true);
        }

        $process->run(function ($type, $line) {
            $this->line($line);
        });

        $filename = $this->findPackageServiceProviderFile($packageName);

        if ($filename === false) {
            $this->comment('The service provider file could not be located. This package may not have any service providers, or may not have named the service provider file correctly.  Cannot finish automated installation.');
            return;
        }

        try {
            if ($this->installServiceProvider($filename)) {
                $this->info('The package and its service provider were installed successfully.');
            } else {
                $this->comment('The package and/or service provider did not install correctly.');
            }
        } catch (ServiceProvidersVariableMissingException $e) {
            $this->comment($e->getMessage());
        } catch (ServiceProviderAlreadyRegisteredException $e) {
            $this->comment($e->getMessage());
        }
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

    protected function getPackagePath($packageName)
    {
        return base_path() . "/vendor/".strtolower($packageName);
    }

    protected function findPackageServiceProviderFile($packageName)
    {
        $dir_iterator = new \RecursiveDirectoryIterator($this->getPackagePath($packageName));
        $iterator = new \RecursiveIteratorIterator($dir_iterator, \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $file) {
            $base = basename($file);

            if (ends_with($base, '/') || $base == ".")
                continue;

            if (preg_match('/^[a-zA-Z0-9_]+ServiceProvider\.php$/',$base)==1) {
                return $file;
            }
        }

        return false;
    }

    protected function extractServiceProviderClassname($data)
    {
        if (preg_match('/\bclass[\s\r\n]*([a-zA-Z0-9_]+)\b/', $data, $m)==1) {
            return $m[1];
        }
        return basename($filename, '.php');
    }

    protected function extractNamespaceFromServiceProvider($data)
    {
        if (preg_match('/namespace\s*([^;]+);/', $data, $m)==1) {
            return trim($m[1]);
        }
        return false;
    }

    protected function generateServiceProviderRegistrationLine($namespace, $classname)
    {
        return $namespace . '\\' . $classname . '::class,';
    }

    protected function installServiceProvider($filename)
    {
        $data = file_get_contents($filename);

        $classname  = $this->extractServiceProviderClassname($data);
        $namespace  = $this->extractNamespaceFromServiceProvider($data);
        $regline    = $this->generateServiceProviderRegistrationLine($namespace, $classname);

        $this->info("Installing $namespace\\$classname Service Provider...");

        $data = file_get_contents(config_path() . '/app.php');

        if (strpos($data, $regline) !== false) {
            throw new ServiceProviderAlreadyRegisteredException("Service provider $namespace\\$classname is already installed.");
        }

        if (strpos($data, '//@@service-providers@@') === false) {
            throw new ServiceProvidersVariableMissingException("The required variable '//@@service-providers@@' was not found in config/app.php.  Please add it to the end of the service providers array to use this command.");
        }

        $count = 0;
        $data = str_replace('//@@service-providers@@', $regline . PHP_EOL . "        //@@service-providers@@".PHP_EOL, $data, $count);

        if ($count > 0) {
            file_put_contents(config_path() . '/app.php', $data);
        }

        return ($count > 0);
    }
}

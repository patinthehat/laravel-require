<?php
namespace LaravelRequire\Console\Commands;

use Illuminate\Console\Command;
use LaravelRequire\Exceptions\InvalidPackageNameException;
use LaravelRequire\Exceptions\ServiceProviderAlreadyRegisteredException;
use LaravelRequire\Exceptions\ServiceProvidersVariableMissingException;
use LaravelRequire\Support\ClassInformationParser;
use LaravelRequire\Support\Packages\LaravelPackage;
use LaravelRequire\Support\Packages\PackageItemInstaller;
use LaravelRequire\Support\Packages\Packages;
use LaravelRequire\Support\ProjectConfiguration;
use LaravelRequire\Support\RegisteredItemInformation;
use Symfony\Component\Process\Process;
use LaravelRequire\Support\ExternalShellCommand;

class RequireCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'require:package {package} ' .
    '{--d | --dry-run : Simulate installation and registration of a package } ' .
    '{--r | --register-only : register package only, don\'t run `composer require`} ' .
    '{--c | --no-class-loader : don\'t use the smart facade class loader}' . '';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install a Laravel package with composer and automatically register its service providers and facades';

    protected $config;

    protected $packages;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->config = new ProjectConfiguration();
        $this->packages = new LaravelPackage();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $packageVers = null;
        $packageName = $this->argument('package');
        $registerOnly = $this->option('register-only');
        $dryRun = $this->option('dry-run');

        if (strpos($packageName,':')!==false) {
            $parts = explode(':', $packageName);
            $packageName = $parts[0];
            $packageVers = $parts[1];
        }
        try {
            Packages::validatePackageName($packageName);
        } catch (InvalidPackageNameException $e) {
            $this->comment($e->getMessage());
            return;
        }

        if ($dryRun) {
            $this->info('[dry-run] run composer require ' . $packageName);
        }

        if (! $registerOnly && ! $dryRun) {
            $composerRequireCommand = $this->findComposerBinary() . " require $packageName";
            if (! is_null($packageVers))
                $composerRequireCommand .= ':'.$packageVers;

            $this->comment("> composer require $packageName...");
            $cmd = new ExternalShellCommand($composerRequireCommand);
            $cmd->run();
        }

        $splist = (array) Packages::findPackageFilesToRegister($packageName);
        $providers = [];
        $facades = [];
        $items = [];

        foreach ($splist as $info) {
            switch ($info->type) {
                case RegisteredItemInformation::SERVICE_PROVIDER_TYPE:
                    $providers[] = $info;
                    break;
                case RegisteredItemInformation::FACADE_TYPE:
                    $facades[] = $info;
                    break;
                default:
                // nothing to do
            }
        }

        $installer = new PackageItemInstaller($this);
        $items = $installer->install($providers, 'Service Provider', null);
        $items2 = $installer->install($facades, 'Facade', null);
        $items = array_merge($items, $items2);

        $installCounter = 0;
        $failedCounter = 0;
        foreach ($items as $item) {
            if (! $item || ! $item->filename)
                continue;

            try {
                $this->info("Registering " . $item->displayName() . ': ' . $item->qualifiedName() . "...");

                if ($dryRun) {
                    $this->info('[dry-run] registerPackageItem');
                }

                if (! $dryRun) {
                    $p = new LaravelPackage;
                    $parser = new ClassInformationParser();
                    $thisBaseNamespace = $parser->getTopLevelNamespace(__NAMESPACE__);

                    if ($p->registerPackageItem($item, $thisBaseNamespace)) {
                        $this->info('...registered successfully.');
                        $installCounter++;
                    } else {
                        $this->comment('The package and/or service provider did not register or install correctly, or it is not a Laravel package.');
                        $failedCounter++;
                    }
                }
            } catch (ServiceProvidersVariableMissingException $e) {
                $this->comment($e->getMessage());
            } catch (ServiceProviderAlreadyRegisteredException $e) {
                $this->comment($e->getMessage());
            }
        } // end foreach(providers)
        $this->info('Finished. '.$installCounter.' items registered'.($failedCounter > 0 ? ", $failedCounter failed." : '.'));
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposerBinary()
    {
        if (file_exists(base_path() . '/composer.phar')) {
            return '"' . PHP_BINARY . '" composer.phar';
        }

        return 'composer';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            [
                'package',
                InputArgument::REQUIRED,
                'The name the package to install and register.'
            ]
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            [
                'register-only',
                'r',
                InputOption::VALUE_NONE,
                'The terminal command that should be assigned.',
                'command:name'
            ]
        ];
    }
}

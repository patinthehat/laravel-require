<?php

namespace LaravelRequire\Support;

use LaravelRequire\Console\Commands\RequireCommand;


/**
 * Handles the prompting of the user as to whether or not they want to register
 * the given service provider or facade.
 *
 */
class PackageItemInstaller
{

    /**
     * @var \LaravelRequire\Console\Commands\RequireCommand
     */
    protected $command;

    public function __construct(RequireCommand $command)
    {
        $this->command = $command;
    }

    /**
     * Returns an array of fully-qualified classnames to register.
     * @param string $data
     * @param string $type
     * @param string $emptyMessage
     * @return array
     */
    public function install($data, $type, $emptyMessage = null)
    {
        $result = [];
        if (count($data) == 1) {
            $this->command->comment(is_null($emptyMessage) ? "No ${type}s found." : null);
            return $result;
        }

        foreach ($data as $item) {
            if ($this->command->confirm("Install $type '$item'?"))
                $result[] = $item;
        }

        return $result;
    }

}

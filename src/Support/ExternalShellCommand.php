<?php

namespace LaravelRequire\Support;

class ExternalShellCommand
{
    
    protected $command;
    
    public function __construct($command)
    {
        $this->command = $command;
    }
    
    public function run()
    {
        $process = new Process($this->command, base_path(), null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            $process->setTty(true);
        }

        $process->run(function ($type, $line) {
            $this->line($line);
        });
    }
    
}

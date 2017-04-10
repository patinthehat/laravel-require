<?php

namespace LaravelRequire\Support;

class ServiceProviderInformation
{

    public $package = '';
    public $classname = '';
    public $extends = '';
    public $namespace = '';
    public $filename = '';
    
    public function __construct()
    {
        //
    }
    
    public function __call($name, $params)
    {
        $this->$name = $params[0];
        return $this;
    }
    
    public function __toString()
    {
        return $this->namespace . '\\'.$this->classname;
    }
    
    public function qualifiedName()
    {
        return $this->namespace . '\\' . $this->classname;
    }
    
}

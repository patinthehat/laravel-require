<?php

namespace LaravelRequire\Support;

/**
 * Stores information on a Service Provider.
 */
class RegisteredItemInformation
{
    const UNDEFINED_TYPE = 0;
    const FACADE_TYPE = 1;
    const SERVICE_PROVIDER_TYPE = 2;

    public $type = self::UNDEFINED_TYPE;

    public $package = '';

    public $classname = '';

    public $extends = '';

    public $namespace = '';

    public $filename = '';

    public $name = '';

    public function __construct(int $type = self::UNDEFINED_TYPE)
    {
        $this->type = $type;
    }

    public function displayName()
    {
        if ($this->type == self::SERVICE_PROVIDER_TYPE)
            return "Service Provider";
        if ($this->type == self::FACADE_TYPE)
            return "Facade";

        return "Unknown";
    }

    /**
     * String representation is the fully-qualified classname.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->qualifiedName();
    }

    /**
     * Get the fully-qualified classname
     *
     * @return string
     */
    public function qualifiedName()
    {
        return $this->namespace . '\\' . $this->classname;
    }

    public function package($value)
    {
        $this->package = $value;
        return $this;
    }

    public function classname($value)
    {
        $this->classname = $value;
        return $this;
    }

    public function extends($value)
    {
        $this->extends = $value;
        return $this;
    }

    public function namespace($value)
    {
        $this->namespace = $value;
        return $this;
    }

    public function filename($value)
    {
        $this->filename = $value;
        return $this;
    }

    public function type($value)
    {
        $this->type = $value;
        return $this;
    }

    public function name($value)
    {
        $this->name = $value;
        return $this;
    }

}

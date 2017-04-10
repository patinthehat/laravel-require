<?php

namespace LaravelRequire\Support;

use LaravelRequire\Support\RegEx;

class PackageFilesFilterIterator extends \RecursiveFilterIterator
{
    public static $compiledRejectFilter = '';

    public static $rejectFilters = array(
        'LICENSE',
        '/(tests|views|node_modules)/',
        '/^\.git(ignore|keep|attributes)?/',
        '/.*\.(xml|md|json|yml|txt|js|php_cs|xml\.dist)$/',
        '/[A-Za-z0-9_]+Test\.php$/',
    );

    protected $regex;

    public function __construct(\RecursiveIterator $iterator)
    {
        parent::__construct($iterator);

        $this->regex = new RegEx();
        //compile all of the filters into one regular expression
        self::$compiledRejectFilter = $this->regex->compileFiltersToRegEx(self::$rejectFilters);
    }

    protected function matches($filename, $filter)
    {
        if ($this->regex->isRegularExpression($filter))
            return (preg_match($filter, $filename)==1);  //filter is a regular expression

        return ($filename == $filter);               //filter requires an exact match
    }

    public function accept()
    {
        $filename = $this->current()->getFilename();

        if ($this->matches($filename, self::$compiledRejectFilter))
            return false;

        return true;
    }

}
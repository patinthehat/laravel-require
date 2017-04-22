<?php

namespace LaravelRequire\Support;

use LaravelRequire\Support\RegEx;

class PackageFilesFilterIterator extends \RecursiveFilterIterator
{

    /**
     *
     * @var string
     */
    public static $compiledRejectFilter = '';

    /**
     *
     * @var string
     */
    public static $rejectFilters = array(
        'LICENSE',
        '/(tests|views|node_modules)/',
        '/^\.git(ignore|keep|attributes)?/',
        '/.*\.(xml|md|json|yml|txt|js|php_cs|xml\.dist)$/',
        '/[A-Za-z0-9_]+Test\.php$/'
    );

    /**
     *
     * @var \LaravelRequire\Support\Regex
     */
    protected $regex;

    public function __construct(\RecursiveIterator $iterator)
    {
        parent::__construct($iterator);

        $this->regex = new RegEx();
        // compile all of the filters into one regular expression
        self::$compiledRejectFilter = $this->regex->compileFiltersToRegEx(self::$rejectFilters);
    }

    protected function matches($filename, $filter)
    {
        if ($this->regex->isRegularExpression($filter))
            return (preg_match($filter, $filename) == 1); // filter is a regular
                                                        // expression

        return ($filename == $filter); // filter requires an exact match
    }

    /**
     * Accept or reject the current file based on the regular expression
     * generated
     * upon class creation.
     *
     * @return boolean
     */
    public function accept()
    {
        $filename = $this->current()->getFilename();

        if ($this->matches($filename, self::$compiledRejectFilter))
            return false;

        return true;
    }

}
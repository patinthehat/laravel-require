<?php

namespace LaravelRequire\Support\Rules;

use LaravelRequire\Support\Rules\MatchFilenameRuleContract;
use LaravelRequire\Support\Rules\MatchSourceCodeRuleContract;


class FacadeRule implements MatchFilenameRuleContract, MatchSourceCodeRuleContract
{
    public function filenameMatch($filename)
    {
       return (preg_match('/^[a-zA-Z0-9_]*Facade\.php$/', basename($filename))==1);
    }

    public function sourceCodeMatch($contents)
    {
        if (preg_match('/\b([a-zA-Z0-9_]+)\s+extends\s+([a-zA-Z0-9_\\\]*Facade)\b/', $contents, $m)==1)
            return $m;

        return false;
    }
}

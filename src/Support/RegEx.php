<?php

namespace LaravelRequire\Support;

class RegEx
{

    public function __construct()
    {
        //
    }

    protected function isBasicCapture($filter)
    {
        return (starts_with($filter,'(') && ends_with($filter, ')'));
    }

    protected function isBasicCaptureWithPipes($filter)
    {
        return ($this->isBasicCapture($filter) && strpos($filter, '|')!==false);
    }

    protected function removeBasicCaptureChars($filter)
    {
        return substr($filter,1,strlen($filter)-2);
    }

    public function compileFiltersToRegEx(array $filters)
    {
        $parts = [];

        foreach($filters as $filter) {
            $expression = $this->getFilterExpressionOnly($filter);

            if ($this->isBasicCaptureWithPipes($expression))
                $expression = $this->removeBasicCaptureChars($expression);
            $parts[] = $expression;
        }
        $result = '/('.implode('|',$parts).')/';

        return $result;
    }

    protected function getFilterExpressionOnly($filter)
    {
        if (!$this->isRegularExpression($filter))
            return $filter;

        return substr($filter, 1, strlen($filter)-2);
    }

    public function isRegularExpression($filter)
    {
        if (strlen($filter) <= 2)
            return false;

        $firstchar  = $filter[0];
        $lastchar   = $filter[strlen($filter)-1];

        return (($firstchar == '/') && ($firstchar == $lastchar));
    }

}

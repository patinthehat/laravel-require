<?php

namespace LaravelRequire\Support\Rules;

use LaravelRequire\Support\Rules\MatchFilenameRuleContract;
use LaravelRequire\Support\Rules\MatchSourceCodeRuleContract;
use LaravelRequire\Support\FacadeClassLoader;


class FacadeRule implements MatchFilenameRuleContract, MatchSourceCodeRuleContract
{
    protected $facadeLoader;

    public function __construct(FacadeClassLoader $facadeLoader)
    {
        $this->facadeLoader = $facadeLoader;
    }

    /**
     * Test a filename to see if it's a Facade.
     * @param string $filename
     * @return boolean
     */
    public function filenameMatch($filename)
    {
       return (preg_match('/^[a-zA-Z0-9_]*Facade\.php$/', basename($filename))==1);
    }

    /**
     * Test the contents of a file to see if the class within it is a Facade class.  If
     * a match is found, use the smart facade class loader to reliably determine the
     * proper name for the Facade.  This is done because Facade filenames can be named
     * just about anything, including 'Facade.php'.  The name is important as it's used
     * during Facade registration.
     * Returns false if no matches are found, and an array of information if a match is
     * found.
     *
     * @param string $contents
     * @return array|boolean
     */
    public function sourceCodeMatch($contents)
    {
        if (preg_match('/\b([a-zA-Z0-9_]+)\s+extends\s+([a-zA-Z0-9_\\\]*Facade)\b/', $contents, $m)==1) {
            $facadeName = $this->facadeLoader->load($contents, true);
            if ($facadeName !== false)
                return ['class'=>$m[1], 'extends'=>$m[2], 'name'=>$facadeName, 'type'=>'facade'];

            return ['class'=>$m[1], 'extends'=>$m[2], 'name'=>$m[1], 'type'=>'facade'];
        }

        return false;
    }
}

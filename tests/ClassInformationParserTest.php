<?php

namespace LaravelRequire\Test;

use LaravelRequire\Support\ClassInformationParser;

class ClassInformationParserTest extends \PHPUnit_Framework_TestCase
{
    protected $parser;

    public function setUp()
    {
        $this->parser = new ClassInformationParser;
    }

    public function tearDown()
    {
        $this->parser = null;
    }

    public function testGetNamespaceSection()
    {
        $ns = 'App\MyProject\AClass';
        $section = 1;
        $this->assertEquals($this->parser->getNamespaceSection($ns, $section), 'App');

        $ns = 'App\MyProject\MyClass2';
        $section = -1000;
        $this->assertEquals($this->parser->getNamespaceSection($ns, $section), 'App');

    }

}

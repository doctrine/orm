<?php

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\Query\Parameter;
use PHPUnit\Framework\TestCase;

class ParameterTest extends TestCase
{
    /**
     * @dataProvider getParameterNamesStartingOrEndingWithAColon
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage A parameter name cannot start or end with ":".
     */
    public function testParameterNameStartingOrEndingWithAColon(string $name): void
    {
        new Parameter($name, 'value');
    }

    /**
     * @return array
     */
    public function getParameterNamesStartingOrEndingWithAColon(): array
    {
        return array(
            array(':name'),
            array('::name'),
            array('name:'),
            array('name::'),
            array(':name:'),
            array('::name::')
        );
    }
}

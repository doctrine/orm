<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Diacritics\NuméroDeTéléphone;
use Doctrine\Tests\OrmFunctionalTestCase;

class DqlWithDiacriticsQueryTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('diacritics');

        parent::setUp();

        $this->generateFixture();
    }

    public function testSimpleQueryWithDiacritics(): void
    {
        $dql = 'SELECT n.numéro ' .
               'FROM Doctrine\Tests\Models\Diacritics\NuméroDeTéléphone n ';

        $result = $this->_em->createQuery($dql)->getSingleScalarResult();

        self::assertEquals('+33000000000', $result);
    }

    public function generateFixture(): void
    {
        $numero = new NuméroDeTéléphone();
        $numero->setNuméro('+33000000000');

        $this->_em->persist($numero);
        $this->_em->flush();
        $this->_em->clear();
    }
}

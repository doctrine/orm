<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\ORM\AbstractQuery;

class AbstractQueryTest extends OrmFunctionalTestCase
{
    use VerifyDeprecations;

    /** @var AbstractQuery */
    private $query;

    protected function setUp(): void
    {
        parent::setUp();
        $this->query = $this->getMockForAbstractClass(AbstractQuery::class, ['em' => $this->_em]);
    }

    public function testSetParameterDeprecationForMissingInferType(): void
    {
        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/issues/8379');

        $this->query->setParameter('test_key', 'test_value');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->query);
    }
}

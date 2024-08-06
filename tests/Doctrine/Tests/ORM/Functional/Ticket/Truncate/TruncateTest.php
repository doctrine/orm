<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\Truncate;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\Tests\Models\Truncate\Truncate;
use Doctrine\Tests\OrmFunctionalTestCase;

class TruncateTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->_em->getConnection()->getDatabasePlatform() instanceof MySQLPlatform) {
            self::markTestSkipped('The ' . self::class . ' requires the use of mysql.');
        }
    }

    public function testSilentlyTruncatingValue(): void
    {
        $this->createSchemaForModels(
            Truncate::class
        );

        $qwerty = 'qwertyuiopasdfghjklzxcvbnm';

        $model = new Truncate();
        $model->setTest($qwerty);

        $this->_em->persist($model);
        $this->_em->flush();
        $this->_em->clear();

        $fresh = $this->_em->find(Truncate::class, $model->getId());

        self::assertSame($qwerty, $fresh->getTest());
    }
}

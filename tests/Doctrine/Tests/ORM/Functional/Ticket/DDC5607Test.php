<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\Tests\OrmTestCase;

class DDC5607Test extends OrmTestCase
{
    public function testOracleColumnAlias()
    {
        $quoteStrategy = new DefaultQuoteStrategy();
        $platform = new OraclePlatform();

        $this->assertSame(
            'THIS_IS_A_29_CHAR_STRING_XX_0',
            $quoteStrategy->getColumnAlias('X_THIS_IS_A_29_CHAR_STRING_XX', 0, $platform)
        );
    }
}
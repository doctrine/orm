<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Tests\OrmFunctionalTestCase;

class SchemaToolTest extends OrmFunctionalTestCase
{
    public function testValidateModelSets(): void
    {
        $schemaTool = new SchemaTool($this->_em);
        $schema     = $schemaTool->getSchemaFromMetadata([]);
        $namespaces = $schema->getNamespaces();

        if ($this->_em->getConnection()->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            self::assertContains('public', $namespaces);
        } else {
            self::assertEmpty($namespaces);
        }
    }
}

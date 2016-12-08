<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Query;

/**
 * @group DDC-2224
 */
class DDC2224Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public static function setUpBeforeClass()
    {
        \Doctrine\DBAL\Types\Type::addType('DDC2224Type', DDC2224Type::class);
    }

    public function testIssue()
    {
        $dql = 'SELECT e FROM ' . __NAMESPACE__ . '\DDC2224Entity e WHERE e.field = :field';
        $query = $this->_em->createQuery($dql);
        $query->setQueryCacheDriver(new ArrayCache());

        $query->setParameter('field', 'test', 'DDC2224Type');
        $this->assertStringEndsWith('.field = FUNCTION(?)', $query->getSQL());

        return $query;
    }

    /**
     * @depends testIssue
     */
    public function testCacheMissWhenTypeChanges(Query $query)
    {
        $query->setParameter('field', 'test', 'string');
        $this->assertStringEndsWith('.field = ?', $query->getSQL());
    }
}

class DDC2224Type extends Type
{
    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getVarcharTypeDeclarationSQL($fieldDeclaration);
    }

    public function getName()
    {
        return 'DDC2224Type';
    }

    /**
     * {@inheritdoc}
     */
    public function canRequireSQLConversion()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform)
    {
        return sprintf('FUNCTION(%s)', $sqlExpr);
    }
}

/**
 * @Entity
 */
class DDC2224Entity
{
    /**
     * @Id @GeneratedValue @Column(type="integer")
     */
    public $id;

    /**
     * @Column(type="DDC2224Type")
     */
    public $field;
}

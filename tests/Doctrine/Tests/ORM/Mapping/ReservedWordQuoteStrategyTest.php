<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ReservedWordQuoteStrategy;
use Doctrine\ORM\Mapping\QuoteStrategy;
use Doctrine\ORM\Mapping\ClassMetadata;

class ReservedWordQuoteStrategyTest extends \Doctrine\Tests\OrmTestCase
{
    /**
     * @var \Doctrine\ORM\Mapping\ReservedWordQuoteStrategy
     */
    private $strategy;

    /**
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    private $platform;

    protected function setUp()
    {
        parent::setUp();
        $em = $this->_getTestEntityManager();
        $this->platform = $em->getConnection()->getDatabasePlatform();
        $this->strategy = new ReservedWordQuoteStrategy();
    }

    /**
     * @param   string $className
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    private function createClassMetadata($className)
    {
        $cm = new ClassMetadata($className);
        $cm->initializeReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);

        return $cm;
    }

    public function generateKeywords()
    {
        $keywords = array_map(
            "strtolower",
            $this->platform->getReservedKeywordsList()
        );

        do {
            $random = substr(str_shuffle(str_repeat("abcdefghijklmnopqrstuvwxyz", 5)), 0, 5);
        } while (!in_array($random, $keywords));

        $data = array();

        foreach ($keywords as $keyword) {
            $data[] = array(
                $keyword,
                $random
            );

            $data[] = array(
                strtoupper($keyword),
                strtoupper($random)
            );
        }

        return $data;
    }

    /**
     * @param string $expected
     * @param string $actual
     * @param string $message
     */
    protected function assertQuoted($expected, $actual, $message = '')
    {
        $this->assertEquals('"' . $expected . '"', $actual, $message);
    }

    /**
     * @dataProvider generateKeywords
     */
    public function testGetColumnName($quoted, $notQuoted)
    {
        $cm = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->mapField(array('fieldName' => 'foo', 'columnName' => $quoted));
        $cm->mapField(array('fieldName' => 'bar', 'columnName' => $notQuoted));
        
        $this->assertEquals($notQuoted, $this->strategy->getColumnName('bar', $cm, $this->platform));
        $this->assertQuoted($quoted, $this->strategy->getColumnName('foo', $cm, $this->platform));
    }

    /**
     * @dataProvider generateKeywords
     */
    public function testGetTableName($quoted, $notQuoted)
    {
        $cm = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->setPrimaryTable(array('name' => $quoted));
        $this->assertQuoted($quoted, $this->strategy->getTableName($cm, $this->platform));

        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);
        $cm->setPrimaryTable(array('name' => $notQuoted));
        $this->assertEquals($notQuoted, $this->strategy->getTableName($cm, $this->platform));
    }

    /**
     * @dataProvider generateKeywords
     */
    public function testJoinTableName($quoted, $notQuoted)
    {
        $cm1 = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress');
        $cm2 = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress');
        
        $cm1->mapManyToMany(array(
            'fieldName'     => 'user',
            'targetEntity'  => 'CmsUser',
            'inversedBy'    => 'users',
            'joinTable'     => array(
                'name'  => $quoted
            )
        ));
        
        $cm2->mapManyToMany(array(
            'fieldName'     => 'user',
            'targetEntity'  => 'CmsUser',
            'inversedBy'    => 'users',
            'joinTable'     => array(
                'name'  => $notQuoted
            )
        ));

        $this->assertQuoted($quoted, $this->strategy->getJoinTableName($cm1->associationMappings['user'], $cm1, $this->platform));
        $this->assertEquals($notQuoted, $this->strategy->getJoinTableName($cm2->associationMappings['user'], $cm2, $this->platform));
       
    }

    /**
     * @dataProvider generateKeywords
     */
    public function testIdentifierColumnNames($quoted, $notQuoted)
    {
        $cm1 = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress');
        $cm2 = $this->createClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress');

        $cm1->mapField(array(
            'id'            => true,
            'fieldName'     => 'id',
            'columnName'    => $quoted,
        ));

        $cm2->mapField(array(
            'id'            => true,
            'fieldName'     => 'id',
            'columnName'    => $notQuoted,
        ));

        $this->assertEquals(array('"' . $quoted . '"'), $this->strategy->getIdentifierColumnNames($cm1, $this->platform));
        $this->assertEquals(array('id'), $this->strategy->getIdentifierColumnNames($cm2, $this->platform));
    }

    public function testColumnAlias()
    {
        $i = 0;
        $this->assertEquals('columnName_0', $this->strategy->getColumnAlias('columnName', $i++, $this->platform));
        $this->assertEquals('column_name_1', $this->strategy->getColumnAlias('column_name', $i++, $this->platform));
        $this->assertEquals('COLUMN_NAME_2', $this->strategy->getColumnAlias('COLUMN_NAME', $i++, $this->platform));
        $this->assertEquals('COLUMNNAME_3', $this->strategy->getColumnAlias('COLUMN-NAME-', $i++, $this->platform));
    }

    /**
     * @dataProvider generateKeywords
     */
    public function testQuoteIdentifierJoinColumns($quoted, $notQuoted)
    {
        $cm1 = $this->createClassMetadata('Doctrine\Tests\Models\DDC117\DDC117ArticleDetails');

        $cm1->mapOneToOne(array(
            'id'            => true,
            'fieldName'     => 'article',
            'targetEntity'  => 'Doctrine\Tests\Models\DDC117\DDC117Article',
            'joinColumns'    => array(array(
                'name'  => $quoted
            )),
        ));

        $this->assertEquals(array('"' . $quoted . '"'), $this->strategy->getIdentifierColumnNames($cm1, $this->platform));

        $cm2 = $this->createClassMetadata('Doctrine\Tests\Models\DDC117\DDC117ArticleDetails');
        $cm2->mapOneToOne(array(
            'id'            => true,
            'fieldName'     => 'article',
            'targetEntity'  => 'Doctrine\Tests\Models\DDC117\DDC117Article',
            'joinColumns'    => array(array(
                'name'  => $notQuoted
            )),
        ));

        $this->assertEquals(array($notQuoted), $this->strategy->getIdentifierColumnNames($cm2, $this->platform));
    }

    /**
     * @dataProvider generateKeywords
     */
    public function testJoinColumnName($quoted, $notQuoted)
    {
        $cm1 = $this->createClassMetadata('Doctrine\Tests\Models\DDC117\DDC117ArticleDetails');

        $cm1->mapOneToOne(array(
            'id'            => true,
            'fieldName'     => 'article',
            'targetEntity'  => 'Doctrine\Tests\Models\DDC117\DDC117Article',
            'joinColumns'    => array(array(
                'name'  => $quoted
            )),
        ));

        $joinColumn = $cm1->associationMappings['article']['joinColumns'][0];
        $this->assertQuoted($quoted, $this->strategy->getJoinColumnName($joinColumn, $cm1, $this->platform));

        $cm2 = $this->createClassMetadata('Doctrine\Tests\Models\DDC117\DDC117ArticleDetails');

        $cm2->mapOneToOne(array(
            'id'            => true,
            'fieldName'     => 'article',
            'targetEntity'  => 'Doctrine\Tests\Models\DDC117\DDC117Article',
            'joinColumns'    => array(array(
                'name'  => $notQuoted
            )),
        ));

        $joinColumn = $cm2->associationMappings['article']['joinColumns'][0];
        $this->assertEquals($notQuoted, $this->strategy->getJoinColumnName($joinColumn, $cm2, $this->platform));
    }

    /**
     * @dataProvider generateKeywords
     */
    public function testReferencedJoinColumnName($quoted, $notQuoted)
    {
        $cm1 = $this->createClassMetadata('Doctrine\Tests\Models\DDC117\DDC117ArticleDetails');

        $cm1->mapOneToOne(array(
            'id'            => true,
            'fieldName'     => 'article',
            'targetEntity'  => 'Doctrine\Tests\Models\DDC117\DDC117Article',
            'joinColumns'    => array(array(
                'name'  => $quoted
            )),
        ));

        $joinColumn = $cm1->associationMappings['article']['joinColumns'][0];
        $this->assertQuoted($quoted, $this->strategy->getReferencedJoinColumnName($joinColumn, $cm1, $this->platform));

        $cm2 = $this->createClassMetadata('Doctrine\Tests\Models\DDC117\DDC117ArticleDetails');

        $cm2->mapOneToOne(array(
            'id'            => true,
            'fieldName'     => 'article',
            'targetEntity'  => 'Doctrine\Tests\Models\DDC117\DDC117Article',
            'joinColumns'    => array(array(
                'name'  => $notQuoted
            )),
        ));

        $joinColumn = $cm2->associationMappings['article']['joinColumns'][0];
        $this->assertEquals($notQuoted, $this->strategy->getReferencedJoinColumnName($joinColumn, $cm2, $this->platform));
    }
}
<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Doctrine\Tests\Models\GH5804\GH5804Article;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group 6402
 */
class GH5804Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('gh5804');
        parent::setUp();
    }

    public static function setUpBeforeClass()
    {
        \Doctrine\DBAL\Types\Type::addType('GH5804Type', GH5804Type::class);
    }

    /**
     * @group GH-5804
     */
    public function testTextColumnSaveAndRetrieve2()
    {
        $firstArticle = new GH5804Article;
        $firstArticle->text = 'Max';
        $this->_em->persist($firstArticle);
        $this->_em->flush();

        self::assertSame(1, $firstArticle->version);

        $firstArticle->text = 'Moritz';
        $this->_em->persist($firstArticle);
        $this->_em->flush();

        self::assertSame(2, $firstArticle->version);

    }
}

class GH5804Generator extends AbstractIdGenerator
{
    /**
     * {@inheritdoc}
     */
    public function generate(EntityManager $em, $entity)
    {
        return 'test5804';
    }
}

class GH5804Type extends \Doctrine\DBAL\Types\Type
{
    public function getName()
    {
        return 'GH5804Type';
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getGuidTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (empty($value)) {
            return null;
        }
        return 'testGh5804DbValue';
    }
}



<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\DDC5889\DDC5889PageModel;
use Doctrine\Tests\Models\DDC5889\DDC5889TranslationModel;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC5889Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->enableSecondLevelCache();

        parent::setUp();

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(DDC5889PageModel::class),
            $this->_em->getClassMetadata(DDC5889TranslationModel::class),
        ));
    }

    public function testSecondLevelCacheDoesReturnCorrectIndex()
    {
        $page = new DDC5889PageModel('phpunit');

        $translation1 = new DDC5889TranslationModel($page, 'phpunit-1', 'en');
        $page->getTranslations()->add($translation1);

        $translation2 = new DDC5889TranslationModel($page, 'phpunit-2', 'pl');
        $page->getTranslations()->add($translation2);

        $this->_em->persist($page);

        $this->_em->flush();
        $this->_em->clear();

        $fetchedPage = $this->_em->find(DDC5889PageModel::class, $page->getId());
        $translations = $fetchedPage->getTranslations();

        $this->assertCount(2, $translations, 'Page does contain translations');

        $this->assertArrayHasKey('en', $translations);
        $this->assertArrayHasKey('pl', $translations);

    }
}

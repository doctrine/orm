<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH12225;

use Doctrine\Tests\OrmFunctionalTestCase;

class GH12225Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            AbstractDirectory::class,
            ConcreteDirectory::class,
        ]);
    }

    public function testHydrateWithIndexByFilterAndInheritanceMapping(): void
    {
        // Enable the filter
        $this->_em->getConfiguration()->addFilter('my_filter', MyFilter::class);
        $this->_em->getFilters()->enable('my_filter');

        // Load entities into database
        $parent = new ConcreteDirectory('parent');
        $child  = (new ConcreteDirectory('child'))->setParent($parent);
        $this->_em->persist($parent);
        $this->_em->persist($child);
        $this->_em->flush();
        $this->_em->clear();

        $repository = $this->_em->getRepository(AbstractDirectory::class);

        // Fetch entities from database while changing filters
        $this->_em->getFilters()->suspend('my_filter');
        $directories = $repository->findBy(['parent' => null]);
        $this->_em->getFilters()->restore('my_filter');

        // Ensure we got the parent directory
        self::assertCount(1, $directories);
        self::assertEquals('parent', $directories[0]->getDirKey());

        // Try to hydrate all children of the parent directory (toArray is important here to initialize the collection)
        self::assertCount(1, $directories[0]->getChildren()->toArray());
    }
}

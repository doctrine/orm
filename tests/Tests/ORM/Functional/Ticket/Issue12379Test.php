<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Issue12379\Node;
use Doctrine\Tests\Models\Issue12379\Page;
use Doctrine\Tests\OrmFunctionalTestCase;

class Issue12379Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            Page::class,
            Node::class,
        );
    }

    public function testLoadingCollectionOnUninitializedProxy(): void
    {
        $page = new Page();
        $node = new Node();

        $page->node = $node;

        $this->_em->persist($page);
        $this->_em->persist($node);

        $this->_em->flush();
        $this->_em->clear();

        $page = $this->_em->getRepository(Page::class)->find($page->id);

        try {
            $this->preventInitializingNode($page);
        } catch (\Exception $e) {

        }

        $this->assertCount(0, $page->node->children);
    }

    private function preventInitializingNode($page): void
    {
        throw new \Exception();
    }
}

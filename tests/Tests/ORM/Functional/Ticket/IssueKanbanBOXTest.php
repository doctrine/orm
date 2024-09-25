<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\IssueKanbanBOX\EntityA;
use Doctrine\Tests\OrmFunctionalTestCase;

class IssueKanbanBOXTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('issueKanbanBOX');

        parent::setUp();
    }

    public function testEntityVersion(): void
    {
        $entityA = new EntityA(1);
        $this->_em->persist($entityA);
        $this->_em->flush();
        $this->_em->clear();
        $dbRetrievedEntityA = $this->_em->getRepository(EntityA::class)->find(1);
        self::assertNull($dbRetrievedEntityA->getVersion());
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Issue9124\Issue9124Group;
use Doctrine\Tests\Models\Issue9124\Issue9124Item;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group issue-9124
 */
class Issue9124Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('issue9124');
        parent::setUp();
    }

    public function testPersistentCollectionUpdatedWhenElementRemoved(): void
    {
        $item1 = new Issue9124Item();
        $this->_em->persist($item1);
        $item2 = new Issue9124Item();
        $this->_em->persist($item2);

        $group = new Issue9124Group();
        $group->items->add($item1);
        $group->items->add($item2);
        $this->_em->persist($group);

        $this->_em->flush();

        $groupId  = $group->id;
        $item1Id = $item1->id;

        // clear entity manager so that $repository->find actually fetches them and uses the hydrator
        // instead of just returning the existing managed entities
        $this->_em->clear();

        $groupRepository = $this->_em->getRepository(Issue9124Group::class);
        $itemRepository = $this->_em->getRepository(Issue9124Item::class);

        /** @var Issue9124Group $group */
        $group = $groupRepository->find($groupId);

        // Extract elements to initialize collection
        $a = $group->items->toArray();

        /** @var Issue9124Item $item */
        $item = $itemRepository->find($item1Id);

        $this->_em->remove($item);

        $this->_em->flush();

        // state should be clean and no errors should happen in a new flush

        $this->_em->flush();
    }
}

<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

/**
 * @group DDC-2790
 */
class DDC2790Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    /**
     * Verifies that entities scheduled for deletion are not treated as updated by UoW,
     * even if their properties are changed after the remove() call
     */
    public function testIssue()
    {
        $this->_em->getEventManager()->addEventListener(Events::onFlush, new OnFlushListener);

        $entity = new CmsUser;
        $entity->username = 'romanb';
        $entity->name = 'Roman';

        $qb = $this->_em->createQueryBuilder();
        $qb->from(get_class($entity), 'c');
        $qb->select("count(c)");
        $initial = intval($qb->getQuery()->getSingleScalarResult());

        $this->_em->persist($entity);
        $this->_em->flush();

        $this->_em->remove($entity);
        // in Doctrine <2.5, this causes an UPDATE statement to be added before the DELETE statement
        // (and consequently also triggers preUpdate/postUpdate for the entity in question)
        $entity->name = 'Robin';

        $this->_em->flush();

        $qb = $this->_em->createQueryBuilder();
        $qb->from(get_class($entity), 'c');
        $qb->select("count(c)");
        $count = intval($qb->getQuery()->getSingleScalarResult());
        $this->assertEquals($initial, $count);
    }
}

class OnFlushListener
{
    /**
     * onFLush listener that tries to cancel deletions by calling persist if the entity is listed
     * as updated in UoW
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        $deletions = $uow->getScheduledEntityDeletions();
        $updates = $uow->getScheduledEntityUpdates();

        $undelete = array_intersect_key($deletions, $updates);
        foreach ($undelete as $d)
        {
            $em->persist($d);
        }
    }
}

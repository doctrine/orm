<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\ORM\Internal\Hydration\ObjectHydrator;
use Doctrine\Tests\Mocks\ArrayResultFactory;

use function array_keys;
use function get_class;

class DontUpdateEqualDatesTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchemaForModels(DontUpdateEqualDatesEntity::class);
    }

    public function testLogicallyEqualDatesDontTriggerUpdate(): void
    {
        $entity = new DontUpdateEqualDatesEntity();
        $sameDate = '19.12.1989 12:34:56.7';
        $dateToPersist = new \DateTime($sameDate);
        
        $entity->setDate($dateToPersist);
        $this->_em->persist($entity);
        $this->_em->flush();
        
        $entityId = $entity->getId();
        
        $this->_em->clear();
        
        $loadedEntity = $this->_em->getRepository(get_class($entity))->find($entityId);

        $dateToPersistAfterLoading = new \DateTime($sameDate);
        
        $loadedEntity->setDate($dateToPersistAfterLoading);
        
        $this->_em->getUnitOfWork()->computeChangeSet($this->_em->getClassMetadata(get_class($entity)), $loadedEntity);
        
        self::assertFalse($this->_em->getUnitOfWork()->isScheduledForUpdate($loadedEntity));
        
    }
}

/** @Entity */
class DontUpdateEqualDatesEntity
{
    
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;
    
    /**
     * @var \DateTime|null
     * @Column(type="datetime", nullable=true)
     */
    public $date;

    public function getId(): int {
        return $this->id;
    }

    public function getDate(): ?\DateTime {
        return $this->date;
    }

    public function setId(int $id): void {
        $this->id = $id;
    }

    public function setDate(?\DateTime $date): void {
        $this->date = $date;
    }



    
   


}

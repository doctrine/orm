<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\TransactionRequiredException;
use Doctrine\Tests\OrmFunctionalTestCase;

final class GH7366Test extends OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->setUpEntitySchema(
            [
                GH7366Entity::class,
            ]
        );

        $this->em->persist(new GH7366Entity('baz'));
        $this->em->flush();
        $this->em->clear();
    }

    public function testOptimisticLockNoExceptionOnFind() : void
    {
        try {
            $entity = $this->em->find(GH7366Entity::class, 1, LockMode::OPTIMISTIC);
        } catch (TransactionRequiredException $e) {
            self::fail('EntityManager::find() threw TransactionRequiredException with LockMode::OPTIMISTIC');
        }
        self::assertEquals('baz', $entity->getName());
    }
}

/**
 * @ORM\Entity
 */
class GH7366Entity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Version
     */
    protected $lockVersion = 1;

    /**
     * @ORM\Column(length=32)
     *
     * @var string
     */
    protected $name;


    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName() : string
    {
        return $this->name;
    }
}

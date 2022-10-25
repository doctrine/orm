<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Version;
use Doctrine\ORM\TransactionRequiredException;
use Doctrine\Tests\OrmFunctionalTestCase;

final class GH7366Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema(
            [
                GH7366Entity::class,
            ],
        );

        $this->_em->persist(new GH7366Entity('baz'));
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testOptimisticLockNoExceptionOnFind(): void
    {
        try {
            $entity = $this->_em->find(GH7366Entity::class, 1, LockMode::OPTIMISTIC);
        } catch (TransactionRequiredException) {
            self::fail('EntityManager::find() threw TransactionRequiredException with LockMode::OPTIMISTIC');
        }

        self::assertEquals('baz', $entity->getName());
    }
}

#[Entity]
class GH7366Entity
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** @var int */
    #[Column(type: 'integer')]
    #[Version]
    protected $lockVersion = 1;

    public function __construct(
        #[Column(length: 32)]
        protected string $name,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilter;

use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\Tests\Models\Company\CompanyContract;
use Doctrine\Tests\Models\Company\CompanyFlexContract;
use Doctrine\Tests\Models\Company\CompanyManager;
use Doctrine\Tests\Models\Company\CompanyPerson;
use Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilter\Entity\Order;
use Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilter\Entity\User;
use Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilter\SQLFilter\CompanySQLFilter;

use function count;

final class ChangeFiltersResultSetMappingTest extends AbstractTestCase
{
    private const FLIPS = 20;

    public function setUp(): void
    {
        $this->useModelSet('company');

        parent::setUp();

        $this->setUpEntitySchema([Order::class, User::class]);

        $this->_em->getConfiguration()->addFilter(CompanySQLFilter::class, CompanySQLFilter::class);
    }

    public function testBasicEntityPersisterKeepsResultSetMappingWhenFilterIsToggled(): void
    {
        $user  = new User('A');
        $order = new Order($user);
        $this->persistFlushClear($user, $order);

        $this->assertResultSetMappingStable(Order::class, function () use ($order, $user): void {
            $found = $this->_em->find(Order::class, $order->id);
            self::assertNotNull($found);
            self::assertSame($user->id, $found->user->id);
        });
    }

    public function testJoinedSubclassPersisterKeepsResultSetMappingWhenFilterIsToggled(): void
    {
        $person = new CompanyPerson();
        $person->setName('Roman');
        $spouse = new CompanyPerson();
        $spouse->setName('Jonathan');
        $person->setSpouse($spouse);
        $this->persistFlushClear($spouse, $person);

        $this->assertResultSetMappingStable(CompanyPerson::class, function () use ($person, $spouse): void {
            $found = $this->_em->find(CompanyPerson::class, $person->getId());
            self::assertNotNull($found);
            self::assertNotNull($found->getSpouse());
            self::assertSame($spouse->getId(), $found->getSpouse()->getId());
        });
    }

    public function testSingleTablePersisterKeepsResultSetMappingWhenFilterIsToggled(): void
    {
        $manager = new CompanyManager();
        $manager->setName('Alexander');
        $manager->setSalary(42);
        $manager->setDepartment('Doctrine');
        $manager->setTitle('Filterer');
        $contract = new CompanyFlexContract();
        $contract->setSalesPerson($manager);
        $this->persistFlushClear($manager, $contract);

        $this->assertResultSetMappingStable(CompanyContract::class, function () use ($contract, $manager): void {
            $found = $this->_em->find(CompanyContract::class, $contract->getId());
            self::assertInstanceOf(CompanyFlexContract::class, $found);
            self::assertSame($manager->getId(), $found->getSalesPerson()->getId());
        });
    }

    /** @param callable(): void $find */
    private function assertResultSetMappingStable(string $className, callable $find): void
    {
        $filters = $this->_em->getFilters();

        $toggle = function () use ($filters, $find): void {
            $filters->disable(CompanySQLFilter::class);
            $this->_em->clear();
            $find();

            $filters->enable(CompanySQLFilter::class)->setParameter('company', 'A');
            $this->_em->clear();
            $find();
        };

        $filters->enable(CompanySQLFilter::class)->setParameter('company', 'A');
        $toggle();

        $before = $this->resultSetMappingSize($className);

        for ($i = 0; $i < self::FLIPS; $i++) {
            $toggle();
        }

        self::assertSame($before, $this->resultSetMappingSize($className));
    }

    /** @return array{type: int, meta: int, columnOwner: int, field: int} */
    private function resultSetMappingSize(string $className): array
    {
        $persister = $this->_em->getUnitOfWork()->getEntityPersister($className);
        self::assertInstanceOf(BasicEntityPersister::class, $persister);
        $rsm = $persister->getResultSetMapping();

        return [
            'type' => count($rsm->typeMappings),
            'meta' => count($rsm->metaMappings),
            'columnOwner' => count($rsm->columnOwnerMap),
            'field' => count($rsm->fieldMappings),
        ];
    }
}

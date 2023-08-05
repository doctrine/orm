<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

use function reset;

class GH10880Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH10880BaseProcess::class,
            GH10880Process::class,
            GH10880ProcessOwner::class,
        ]);
    }

    public function testProcessShouldBeUpdated(): void
    {
        $process              = new GH10880Process();
        $process->description = 'first value';

        $owner          = new GH10880ProcessOwner();
        $owner->process = $process;

        $this->_em->persist($process);
        $this->_em->persist($owner);
        $this->_em->flush();
        $this->_em->clear();

        $ownerLoaded                       = $this->_em->getRepository(GH10880ProcessOwner::class)->find($owner->id);
        $ownerLoaded->process->description = 'other description';

        $queryLog = $this->getQueryLog();
        $queryLog->reset()->enable();
        $this->_em->flush();

        $this->removeTransactionCommandsFromQueryLog();

        self::assertCount(1, $queryLog->queries);
        $query = reset($queryLog->queries);
        self::assertSame('UPDATE GH10880BaseProcess SET description = ? WHERE id = ?', $query['sql']);
    }

    private function removeTransactionCommandsFromQueryLog(): void
    {
        $log = $this->getQueryLog();

        foreach ($log->queries as $key => $entry) {
            if ($entry['sql'] === '"START TRANSACTION"' || $entry['sql'] === '"COMMIT"') {
                unset($log->queries[$key]);
            }
        }
    }
}

/**
 * @ORM\Entity
 */
class GH10880ProcessOwner
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    public $id;

    /**
     * fetch=EAGER is important to reach the part of \Doctrine\ORM\UnitOfWork::createEntity()
     * that is important for this regression test
     *
     * @ORM\ManyToOne(targetEntity="GH10880Process", fetch="EAGER")
     *
     * @var GH10880Process
     */
    public $process;
}

/**
 * @ORM\Entity()
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"process" = "GH10880Process"})
 */
abstract class GH10880BaseProcess
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    public $description;
}

/**
 * @ORM\Entity
 */
class GH10880Process extends GH10880BaseProcess
{
}

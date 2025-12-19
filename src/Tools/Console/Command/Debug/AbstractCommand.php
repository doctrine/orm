<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Console\Command\Debug;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;

use function assert;

/** @internal */
abstract class AbstractCommand extends Command
{
    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {
        parent::__construct();
    }

    final protected function getEntityManager(string $name): EntityManagerInterface
    {
        $manager = $this->getManagerRegistry()->getManager($name);

        assert($manager instanceof EntityManagerInterface);

        return $manager;
    }

    final protected function getManagerRegistry(): ManagerRegistry
    {
        return $this->managerRegistry;
    }
}

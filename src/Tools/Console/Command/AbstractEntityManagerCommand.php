<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Console\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

abstract class AbstractEntityManagerCommand extends Command
{
    public function __construct(private readonly EntityManagerProvider $entityManagerProvider)
    {
        parent::__construct();
    }

    final protected function getEntityManager(InputInterface $input): EntityManagerInterface
    {
        return $input->getOption('em') === null
            ? $this->entityManagerProvider->getDefaultManager()
            : $this->entityManagerProvider->getManager($input->getOption('em'));
    }
}

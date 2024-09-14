<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Console;

use ReflectionMethod;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

if ((new ReflectionMethod(Command::class, 'execute'))->hasReturnType()) {
    /** @internal */
    trait CommandCompatibility
    {
        protected function execute(InputInterface $input, OutputInterface $output): int
        {
            return $this->doExecute($input, $output);
        }
    }
} else {
    /** @internal */
    trait CommandCompatibility
    {
        /**
         * {@inheritDoc}
         *
         * @return int
         */
        protected function execute(InputInterface $input, OutputInterface $output)
        {
            return $this->doExecute($input, $output);
        }
    }
}

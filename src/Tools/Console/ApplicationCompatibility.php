<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

use function method_exists;

/**
 * Forward compatibility with Symfony Console 7.4
 *
 * @internal
 */
trait ApplicationCompatibility
{
    private static function addCommandToApplication(Application $application, Command $command): Command|null
    {
        // @phpstan-ignore function.alreadyNarrowedType (This method did not exist before Symfony 7.4)
        if (method_exists(Application::class, 'addCommand')) {
            return $application->addCommand($command);
        }

        // @phpstan-ignore method.notFound
        return $application->add($command);
    }
}

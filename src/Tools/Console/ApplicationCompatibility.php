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
        if (method_exists(Application::class, 'addCommand')) {
            // @phpstan-ignore method.notFound (This method will be added in Symfony 7.4)
            return $application->addCommand($command);
        }

        return $application->add($command);
    }
}

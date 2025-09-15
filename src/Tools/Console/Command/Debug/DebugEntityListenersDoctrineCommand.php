<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Console\Command\Debug;

use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function array_keys;
use function array_merge;
use function array_unique;
use function array_values;
use function assert;
use function class_exists;
use function ksort;
use function ltrim;
use function sort;
use function sprintf;

final class DebugEntityListenersDoctrineCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->setName('orm:debug:entity-listeners')
            ->setDescription('Lists entity listeners for a given entity')
            ->addArgument('entity', InputArgument::OPTIONAL, 'The fully-qualified entity class name')
            ->addArgument('event', InputArgument::OPTIONAL, 'The event name to filter by (e.g. postPersist)')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command lists all entity listeners for a given entity:

    <info>php %command.full_name% 'App\Entity\User'</info>

To show only listeners for a specific event, pass the event name:

    <info>php %command.full_name% 'App\Entity\User' postPersist</info>
EOT);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var class-string|null $entityName */
        $entityName = $input->getArgument('entity');

        if ($entityName === null) {
            /** @var class-string $entityName */
            $entityName = $io->choice('Which entity do you want to list listeners for?', $this->listAllEntities());
        }

        $entityName    = ltrim($entityName, '\\');
        $entityManager = $this->getManagerRegistry()->getManagerForClass($entityName);

        if ($entityManager === null) {
            $io->error(sprintf('No entity manager found for class "%s".', $entityName));

            return self::FAILURE;
        }

        $classMetadata = $entityManager->getClassMetadata($entityName);
        assert($classMetadata instanceof ClassMetadata);

        $eventName = $input->getArgument('event');

        $allListeners = $eventName === null
            ? $classMetadata->entityListeners
            : [$eventName => $classMetadata->entityListeners[$eventName] ?? []];

        ksort($allListeners);

        $io->title(sprintf('Entity listeners for <info>%s</info>', $entityName));

        if (! $allListeners) {
            $io->text('No listeners are configured for this entity.');

            return self::SUCCESS;
        }

        foreach ($allListeners as $event => $listeners) {
            $io->section(sprintf('"%s" event', $event));

            if (! $listeners) {
                $io->text('No listeners are configured for this event.');
                continue;
            }

            $rows = [];
            foreach ($listeners as $order => $listener) {
                $rows[] = [sprintf('#%d', ++$order), sprintf('%s::%s()', $listener['class'], $listener['method'])];
            }

            $io->table(['Order', 'Listener'], $rows);
        }

        return self::SUCCESS;
    }

    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        if ($input->mustSuggestArgumentValuesFor('entity')) {
            $suggestions->suggestValues($this->listAllEntities());

            return;
        }

        if ($input->mustSuggestArgumentValuesFor('event')) {
            $entityName = ltrim($input->getArgument('entity'), '\\');

            if (! class_exists($entityName)) {
                return;
            }

            $entityManager = $this->getManagerRegistry()->getManagerForClass($entityName);

            if ($entityManager === null) {
                return;
            }

            $classMetadata = $entityManager->getClassMetadata($entityName);
            assert($classMetadata instanceof ClassMetadata);

            $suggestions->suggestValues(array_keys($classMetadata->entityListeners));

            return;
        }
    }

    /** @return list<class-string> */
    private function listAllEntities(): array
    {
        $entities = [];
        foreach (array_keys($this->getManagerRegistry()->getManagerNames()) as $managerName) {
            $entities[] = $this->getEntityManager($managerName)->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();
        }

        $entities = array_values(array_unique(array_merge(...$entities)));

        sort($entities);

        return $entities;
    }
}

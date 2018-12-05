<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Console\Command\ClearCache;

use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\XcacheCache;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to clear the metadata cache of the various cache drivers.
 */
class MetadataCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('orm:clear-cache:metadata')
             ->setDescription('Clear all metadata cache of the various cache drivers')
             ->addOption('flush', null, InputOption::VALUE_NONE, 'If defined, cache entries will be flushed instead of deleted/invalidated.')
             ->setHelp(<<<'EOT'
The <info>%command.name%</info> command is meant to clear the metadata cache of associated Entity Manager.
It is possible to invalidate all cache entries at once - called delete -, or flushes the cache provider
instance completely.

The execution type differ on how you execute the command.
If you want to invalidate the entries (and not delete from cache instance), this command would do the work:

<info>%command.name%</info>

Alternatively, if you want to flush the cache provider using this command:

<info>%command.name% --flush</info>

Finally, be aware that if <info>--flush</info> option is passed, not all cache providers are able to flush entries,
because of a limitation of its execution nature.
EOT
             );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ui = new SymfonyStyle($input, $output);

        $em          = $this->getHelper('em')->getEntityManager();
        $cacheDriver = $em->getConfiguration()->getMetadataCacheImpl();

        if (! $cacheDriver) {
            throw new InvalidArgumentException('No Metadata cache driver is configured on given EntityManager.');
        }

        if ($cacheDriver instanceof ApcCache) {
            throw new LogicException('Cannot clear APC Cache from Console, it is shared in the Webserver memory and not accessible from the CLI.');
        }

        if ($cacheDriver instanceof ApcuCache) {
            throw new LogicException('Cannot clear APCu Cache from Console, it is shared in the Webserver memory and not accessible from the CLI.');
        }

        if ($cacheDriver instanceof XcacheCache) {
            throw new LogicException('Cannot clear XCache Cache from Console, it is shared in the Webserver memory and not accessible from the CLI.');
        }

        $ui->comment('Clearing <info>all</info> Metadata cache entries');

        $result  = $cacheDriver->deleteAll();
        $message = $result ? 'Successfully deleted cache entries.' : 'No cache entries were deleted.';

        if ($input->getOption('flush') === true) {
            $result  = $cacheDriver->flushAll();
            $message = $result ? 'Successfully flushed cache entries.' : $message;
        }

        if (! $result) {
            $ui->error($message);

            return 1;
        }

        $ui->success($message);

        return 0;
    }
}

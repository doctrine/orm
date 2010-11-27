<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Tools\Console\Command\ClearCache;

use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console;

/**
 * Command to clear the result cache of the various cache drivers.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class ResultCommand extends Console\Command\Command
{
    /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this
        ->setName('orm:clear-cache:result')
        ->setDescription('Clear result cache of the various cache drivers.')
        ->setDefinition(array(
            new InputOption(
                'id', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'ID(s) of the cache entry to delete (accepts * wildcards).', array()
            ),
            new InputOption(
                'regex', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Delete cache entries that match the given regular expression(s).', array()
            ),
            new InputOption(
                'prefix', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Delete cache entries that have the given prefix(es).', array()
            ),
            new InputOption(
                'suffix', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Delete cache entries that have the given suffix(es).', array()
            ),
        ))
        ->setHelp(<<<EOT
Clear result cache of the various cache drivers.
If none of the options are defined, all cache entries will be removed.
EOT
        );
    }

    /**
     * @see Console\Command\Command
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $em = $this->getHelper('em')->getEntityManager();
        $cacheDriver = $em->getConfiguration()->getResultCacheImpl();

        if ( ! $cacheDriver) {
            throw new \InvalidArgumentException('No Result cache driver is configured on given EntityManager.');
        }

        if ($cacheDriver instanceof \Doctrine\Common\Cache\ApcCache) {
            throw new \LogicException("Cannot clear APC Cache from Console, its shared in the Webserver memory and not accessible from the CLI.");
        }

        $outputed = false;

        // Removing based on --id
        if (($ids = $input->getOption('id')) !== null && $ids) {
            foreach ($ids as $id) {
                $output->write($outputed ? PHP_EOL : '');
                $output->write(sprintf('Clearing Result cache entries that match the id "<info>%s</info>"', $id) . PHP_EOL);

                $deleted = $cacheDriver->delete($id);

                if (is_array($deleted)) {
                    $this->_printDeleted($output, $deleted);
                } else if (is_bool($deleted) && $deleted) {
                    $this->_printDeleted($output, array($id));
                }

                $outputed = true;
            }
        }

        // Removing based on --regex
        if (($regexps = $input->getOption('regex')) !== null && $regexps) {
            foreach($regexps as $regex) {
                $output->write($outputed ? PHP_EOL : '');
                $output->write(sprintf('Clearing Result cache entries that match the regular expression "<info>%s</info>"', $regex) . PHP_EOL);

                $this->_printDeleted($output, $cacheDriver->deleteByRegex('/' . $regex. '/'));

                $outputed = true;
            }
        }

        // Removing based on --prefix
        if (($prefixes = $input->getOption('prefix')) !== null & $prefixes) {
            foreach ($prefixes as $prefix) {
                $output->write($outputed ? PHP_EOL : '');
                $output->write(sprintf('Clearing Result cache entries that have the prefix "<info>%s</info>"', $prefix) . PHP_EOL);

                $this->_printDeleted($output, $cacheDriver->deleteByPrefix($prefix));

                $outputed = true;
            }
        }

        // Removing based on --suffix
        if (($suffixes = $input->getOption('suffix')) !== null && $suffixes) {
            foreach ($suffixes as $suffix) {
                $output->write($outputed ? PHP_EOL : '');
                $output->write(sprintf('Clearing Result cache entries that have the suffix "<info>%s</info>"', $suffix) . PHP_EOL);

                $this->_printDeleted($output, $cacheDriver->deleteBySuffix($suffix));

                $outputed = true;
            }
        }

        // Removing ALL entries
        if ( ! $ids && ! $regexps && ! $prefixes && ! $suffixes) {
            $output->write($outputed ? PHP_EOL : '');
            $output->write('Clearing ALL Result cache entries' . PHP_EOL);

            $this->_printDeleted($output, $cacheDriver->deleteAll());

            $outputed = true;
        }
    }

    private function _printDeleted(Console\Output\OutputInterface $output, array $items)
    {
        if ($items) {
            foreach ($items as $item) {
                $output->write(' - ' . $item . PHP_EOL);
            }
        } else {
            $output->write('No entries to be deleted.' . PHP_EOL);
        }
    }
}

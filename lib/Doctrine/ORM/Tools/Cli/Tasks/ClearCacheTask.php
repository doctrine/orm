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
 
namespace Doctrine\ORM\Tools\Cli\Tasks;

use Doctrine\Common\Cache\AbstractDriver;

/**
 * CLI Task to clear the cache of the various cache drivers
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class ClearCacheTask extends AbstractTask
{
    public function basicHelp()
    {
        $this->_writeSynopsis($this->getPrinter());
    }

    public function extendedHelp()
    {
        $printer = $this->getPrinter();
    
        $printer->write('Task: ')->writeln('clear-cache', 'KEYWORD')
                ->write('Synopsis: ');
        $this->_writeSynopsis($printer);

        $printer->writeln('Description: Clear cache from configured query, result and metadata drivers.')
                ->writeln('Options:')
                ->write('--query', 'OPT_ARG')
                ->writeln("\t\t\tClear the query cache.")
                ->write('--result', 'OPT_ARG')
                ->writeln("\t\tClear the result cache.")
                ->write('--metadata', 'OPT_ARG')
                ->writeln("\t\tClear the metadata cache.")
                ->write('--id=<ID>', 'REQ_ARG')
                ->writeln("\t\tThe id of the cache entry to delete (accepts * wildcards).")
                ->write('--regex=<REGEX>', 'REQ_ARG')
                ->writeln("\t\tDelete cache entries that match the given regular expression.")
                ->write('--prefix=<PREFIX>', 'REQ_ARG')
                ->writeln("\tDelete cache entries that have the given prefix.")
                ->write('--suffix=<SUFFIX>', 'REQ_ARG')
                ->writeln("\tDelete cache entries that have the given suffix.");
    }

    private function _writeSynopsis($printer)
    {
        $printer->write('clear-cache', 'KEYWORD')
                ->write(' (--query | --result | --metadata)', 'OPT_ARG')
                ->write(' [--id=<ID>]', 'REQ_ARG')
                ->write(' [--regex=<REGEX>]', 'REQ_ARG')
                ->write(' [--prefix=<PREFIX>]', 'REQ_ARG')
                ->writeln(' [--suffix=<SUFFIX>]', 'REQ_ARG');
    }

    public function validate()
    {
        if ( ! parent::validate()) {
            return false;
        }

        $printer = $this->getPrinter();
        $args = $this->getArguments();

        // When clearing the query cache no need to specify
        // id, regex, prefix or suffix.
        if ((isset($args['query']) || isset($args['metadata']))
            && (isset($args['id'])
                || isset($args['regex'])
                || isset($args['prefix'])
                || isset($args['suffix']))) {

            $printer->writeln('When clearing the query or metadata cache do not specify any --id, --regex, --prefix or --suffix.', 'ERROR');

            return false;
        }

        return true;
    }

    public function run()
    {
        $printer = $this->getPrinter();
        $args = $this->getArguments();

        $query = isset($args['query']);
        $result = isset($args['result']);
        $metadata = isset($args['metadata']);
        $id = isset($args['id']) ? $args['id'] : null;
        $regex = isset($args['regex']) ? $args['regex'] : null;
        $prefix = isset($args['prefix']) ? $args['prefix'] : null;
        $suffix = isset($args['suffix']) ? $args['suffix'] : null;

        $all = false;
        if ( ! $query && ! $result && ! $metadata) {
            $all = true;
        }

        $configuration = $this->_em->getConfiguration();

        if ($query || $all) {
            $this->_doDelete(
                'query',
                $configuration->getQueryCacheImpl(),
                $id,
                $regex,
                $prefix,
                $suffix
            );
        }

        if ($result || $all) {
            $this->_doDelete(
                'result',
                $configuration->getResultCacheImpl(),
                $id,
                $regex,
                $prefix,
                $suffix
            );
        }

        if ($metadata || $all) {
            $this->_doDelete(
                'metadata',
                $configuration->getMetadataCacheImpl(),
                $id,
                $regex,
                $prefix,
                $suffix
            );
        }
    }

    private function _doDelete($type, $cacheDriver, $id, $regex, $prefix, $suffix)
    {
        $printer = $this->getPrinter();

        if ( ! $cacheDriver) {
            $printer->writeln('No driver has been configured for the ' . $type . ' cache.', 'ERROR');
            return false;
        }

        if ($id) {
            $printer->writeln('Clearing ' . $type . ' cache entries that match the id "' . $id . '"', 'INFO');

            $deleted = $cacheDriver->delete($id);
            if (is_array($deleted)) {
                $this->_printDeleted($printer, $type, $deleted);
            } else if (is_bool($deleted) && $deleted) {
                $this->_printDeleted($printer, $type, array($id));
            }
        }

        if ($regex) {
            $printer->writeln('Clearing ' . $type . ' cache entries that match the regular expression "' . $regex . '"', 'INFO');

            $this->_printDeleted($printer, $type, $cacheDriver->deleteByRegex('/' . $regex. '/'));
        }

        if ($prefix) {
            $printer->writeln('Clearing ' . $type . ' cache entries that have the prefix "' . $prefix . '"', 'INFO');

            $this->_printDeleted($printer, $type, $cacheDriver->deleteByPrefix($prefix));
        }

        if ($suffix) {
            $printer->writeln('Clearing ' . $type . ' cache entries that have the suffix "' . $suffix . '"', 'INFO');

            $this->_printDeleted($printer, $type, $cacheDriver->deleteBySuffix($suffix));
        }

        if ( ! $id && ! $regex && ! $prefix && ! $suffix) {
            $printer->writeln('Clearing all ' . $type . ' cache entries', 'INFO');

            $this->_printDeleted($printer, $type, $cacheDriver->deleteAll());
        }
    }

    private function _printDeleted($printer, $type, array $ids)
    {
        if ( ! empty($ids)) {
            foreach ($ids as $id) {
                $printer->writeln(' - ' . $id);
            }
        } else {
            $printer->writeln('No ' . $type . ' cache entries found', 'ERROR');
        }
        $printer->writeln("");
    }
}
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

use Doctrine\Common\Cli\Tasks\AbstractTask,
    Doctrine\Common\Cli\CliException,
    Doctrine\Common\Cli\Option,
    Doctrine\Common\Cli\OptionGroup,
    Doctrine\Common\Cache\AbstractDriver;

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
    /**
     * @inheritdoc
     */
    public function buildDocumentation()
    {
        $cacheOptions = new OptionGroup(OptionGroup::CARDINALITY_1_1, array(
            new Option('query', null, 'Clear the query cache.'),
            new Option('metadata', null, 'Clear the metadata cache.'),
            new OptionGroup(OptionGroup::CARDINALITY_M_N, array(
                new OptionGroup(OptionGroup::CARDINALITY_1_1, array(
                    new Option('result', null, 'Clear the result cache.')
                )), 
                new OptionGroup(OptionGroup::CARDINALITY_0_N, array(
                    new Option('id', '<ID>', 'The id of the cache entry to delete (accepts * wildcards).'),
                    new Option('regex', '<REGEX>', 'Delete cache entries that match the given regular expression.'),
                    new Option('prefix', '<PREFIX>', 'Delete cache entries that have the given prefix.'),
                    new Option('suffix', '<SUFFIX>', 'Delete cache entries that have the given suffix.')
                ))
            ))
        ));
        
        $doc = $this->getDocumentation();
        $doc->setName('clear-cache')
            ->setDescription('Clear cache from configured query, result and metadata drivers.')
            ->getOptionGroup()
                ->addOption($cacheOptions);
    }

    /**
     * @inheritdoc
     */
    public function validate()
    {
        $arguments = $this->getArguments();
        
        // Check if we have an active EntityManager
        $em = $this->getConfiguration()->getAttribute('em');
        
        if ($em === null) {
            throw new CliException(
                "Attribute 'em' of CLI Configuration is not defined or it is not a valid EntityManager."
            );
        }
        
        // When clearing the query cache no need to specify
        // id, regex, prefix or suffix.
        if (
            (isset($arguments['query']) || isset($arguments['metadata'])) && (isset($arguments['id']) || 
            isset($arguments['regex']) || isset($arguments['prefix']) || isset($arguments['suffix']))
        ) {
            throw new CliException(
                'When clearing the query or metadata cache do not ' .
                'specify any --id, --regex, --prefix or --suffix.'
            );
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $arguments = $this->getArguments();
        $printer = $this->getPrinter();
        
        $query = isset($arguments['query']);
        $result = isset($arguments['result']);
        $metadata = isset($arguments['metadata']);
        $id = isset($arguments['id']) ? $arguments['id'] : null;
        $regex = isset($arguments['regex']) ? $arguments['regex'] : null;
        $prefix = isset($arguments['prefix']) ? $arguments['prefix'] : null;
        $suffix = isset($arguments['suffix']) ? $arguments['suffix'] : null;

        $all = false;
        
        if ( ! $query && ! $result && ! $metadata) {
            $all = true;
        }

        $em = $this->getConfiguration()->getAttribute('em');
        $configuration = $em->getConfiguration();

        if ($query || $all) {
            $this->_doDelete(
                'query', $configuration->getQueryCacheImpl(), $id, $regex, $prefix, $suffix
            );
        }

        if ($result || $all) {
            $this->_doDelete(
                'result', $configuration->getResultCacheImpl(), $id, $regex, $prefix, $suffix
            );
        }

        if ($metadata || $all) {
            $this->_doDelete(
                'metadata', $configuration->getMetadataCacheImpl(), $id, $regex, $prefix, $suffix
            );
        }
    }

    private function _doDelete($type, $cacheDriver, $id, $regex, $prefix, $suffix)
    {
        $printer = $this->getPrinter();

        if ( ! $cacheDriver) {
            throw new CliException('No driver has been configured for the ' . $type . ' cache.');
        }

        if ($id) {
            $printer->writeln('Clearing ' . $type . ' cache entries that match the id "' . $id . '".', 'INFO');

            $deleted = $cacheDriver->delete($id);
            
            if (is_array($deleted)) {
                $this->_printDeleted($type, $deleted);
            } else if (is_bool($deleted) && $deleted) {
                $this->_printDeleted($type, array($id));
            }
        }

        if ($regex) {
            $printer->writeln('Clearing ' . $type . ' cache entries that match the regular expression ".' . $regex . '"', 'INFO');

            $this->_printDeleted($type, $cacheDriver->deleteByRegex('/' . $regex. '/'));
        }

        if ($prefix) {
            $printer->writeln('Clearing ' . $type . ' cache entries that have the prefix "' . $prefix . '".', 'INFO');

            $this->_printDeleted($type, $cacheDriver->deleteByPrefix($prefix));
        }

        if ($suffix) {
            $printer->writeln('Clearing ' . $type . ' cache entries that have the suffix "' . $suffix . '".', 'INFO');

            $this->_printDeleted($type, $cacheDriver->deleteBySuffix($suffix));
        }

        if ( ! $id && ! $regex && ! $prefix && ! $suffix) {
            $printer->writeln('Clearing all ' . $type . ' cache entries.', 'INFO');

            $this->_printDeleted($type, $cacheDriver->deleteAll());
        }
    }

    private function _printDeleted($type, array $ids)
    {
        $printer = $this->getPrinter();
    
        if ( ! empty($ids)) {
            foreach ($ids as $id) {
                $printer->writeln(' - ' . $id);
            }
        } else {
            throw new CliException('No ' . $type . ' cache entries found.');
        }
        
        $printer->write(PHP_EOL);
    }
}
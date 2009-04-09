<?php
/*
 *  $Id: Cache.php 3938 2008-03-06 19:36:50Z romanb $
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

namespace Doctrine\ORM\Query;

/**
 * Doctrine\ORM\Query\CacheHandler
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       2.0
 * @version     $Revision: 1393 $
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 *
 * @todo Re-document this class
 */
abstract class CacheHandler
{
    /**
     * Static factory method. Receives a Doctrine_ORM_Query object and generates
     * the object after processing queryComponents. Table aliases are retrieved
     * directly from Doctrine_ORM_Query_Parser.
     *
     * @param mixed $result Data to be stored.
     * @param Doctrine_ORM_Query_ParserResult $parserResult Parser results that enables to have important data retrieved.
     */
    public static function fromResultSet($result, $parserResult)
    {
        $queryComponents = array();

        foreach ($parserResult->getQueryComponents() as $alias => $components) {
            if ( ! isset($components['parent'])) {
                $queryComponents[$alias][] = $components['mapper']->getComponentName();
                //$queryComponents[$alias][] = $components['mapper']->getComponentName();
            } else {
                $queryComponents[$alias][] = $components['parent'] . '.' . $components['relation']->getAlias();
            }

            if (isset($components['agg'])) {
                $queryComponents[$alias][] = $components['agg'];
            }

            if (isset($components['map'])) {
                $queryComponents[$alias][] = $components['map'];
            }
        }

        return new QueryResult(
            $result,
            $queryComponents,
            $parserResult->getTableAliasMap(),
            $parserResult->getEnumParams()
        );
    }

    /**
     * Static factory method. Receives a Doctrine_ORM_Query object and a cached data.
     * It handles the cache and generates the object after processing queryComponents.
     * Table aliases are retrieved from cache.
     *
     * @param Doctrine_ORM_Query $query Doctrine_ORM_Query_Object related to this cache item.
     * @param mixed $cached Cached data.
     */
    public static function fromCachedResult($query, $cached = false)
    {
        $cached = unserialize($cached);

        return new QueryResult(
            $cached[0],
            self::_getQueryComponents($cached[1]),
            $cached[2],
            $cached[3]
        );
    }

    /**
     * Static factory method. Receives a Doctrine_ORM_Query object and a cached data.
     * It handles the cache and generates the object after processing queryComponents.
     * Table aliases are retrieved from cache.
     *
     * @param Doctrine_ORM_Query $query Doctrine_ORM_Query_Object related to this cache item.
     * @param mixed $cached Cached data.
     */
    public static function fromCachedQuery($query, $cached = false)
    {
        $cached = unserialize($cached);

        return new ParserResult(
            $cached[0],
            self::_getQueryComponents($cached[1]),
            $cached[2],
            $cached[3]
        );
    }

    /**
     * @nodoc
     */
    protected static function _getQueryComponents($query, $cachedQueryComponents)
    {
        $queryComponents = array();

        foreach ($cachedQueryComponents as $alias => $components) {
            $e = explode('.', $components[0]);

            if (count($e) === 1) {
                $queryComponents[$alias]['mapper'] = $query->getConnection()->getMapper($e[0]);
                $queryComponents[$alias]['table'] = $queryComponents[$alias]['mapper']->getTable();
            } else {
                $queryComponents[$alias]['parent'] = $e[0];
                $queryComponents[$alias]['relation'] = $queryComponents[$e[0]]['table']->getAssociation($e[1]);
                $queryComponents[$alias]['mapper'] = $query->getConnection()->getMapper($queryComponents[$alias]['relation']->getTargetEntityName());
                $queryComponents[$alias]['table'] = $queryComponents[$alias]['mapper']->getTable();
            }

            if (isset($v[1])) {
                $queryComponents[$alias]['agg'] = $components[1];
            }

            if (isset($v[2])) {
                $queryComponents[$alias]['map'] = $components[2];
            }
        }

        return $queryComponents;
    }
}
<?php
/*
 *  $Id: Orderby.php 1871 2007-06-27 17:41:02Z zYne $
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
 * <http://www.phpdoctrine.com>.
 */
Doctrine::autoload('Doctrine_Query_Part');
/**
 * Doctrine_Query_Orderby
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1871 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Query_Orderby extends Doctrine_Query_Part
{
    /**
     * DQL ORDER BY PARSER
     * parses the order by part of the query string
     *
     * @param string $str
     * @return void
     */
    public function parse($str, $append = false)
    {
        $ret = array();

        foreach (explode(',', trim($str)) as $r) {
            $r = trim($r);
            $e = explode(' ', $r);
            $a = explode('.', $e[0]);

            if (count($a) > 1) {
                $field     = array_pop($a);
                $reference = implode('.', $a);
                $name      = end($a);

                $map = $this->query->load($reference, false);
                $tableAlias = $this->query->getTableAlias($reference);

                $r = $tableAlias . '.' . $field;


            } else {
                $field = $this->query->getAggregateAlias($e[0]);

                $r = $field;
            }
            if (isset($e[1])) {
                $r .= ' ' . $e[1];
            }
            $ret[] = $r;
        }
        return $ret;
    }
}

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
 * <http://www.phpdoctrine.com>.
 */
Doctrine::autoload('Doctrine_Connection_Module');
/**
 * Doctrine_Formatter
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Formatter extends Doctrine_Connection_Module
{
    /**
     * Quotes pattern (% and _) characters in a string)
     *
     * EXPERIMENTAL
     *
     * WARNING: this function is experimental and may change signature at
     * any time until labelled as non-experimental
     *
     * @param   string  the input string to quote
     *
     * @return  string  quoted string
     */
    public function escapePattern($text)
    {
        if ($this->string_quoting['escape_pattern']) {
            $tmp = $this->conn->string_quoting;

            $text = str_replace($tmp['escape_pattern'], 
                                $tmp['escape_pattern'] .
                                $tmp['escape_pattern'], $text);

            foreach ($this->wildcards as $wildcard) {
                $text = str_replace($wildcard, $tmp['escape_pattern'] . $wildcard, $text);
            }
        }
        return $text;
    }
    /**
     * convertBooleans
     * some drivers need the boolean values to be converted into integers
     * when using DQL API
     *
     * This method takes care of that conversion
     *
     * @param array $item
     * @return void
     */
    public function convertBooleans($item)
    {
    	if (is_array($item)) {
            foreach ($item as $k => $value) {
                if (is_bool($value)) {
                    $item[$k] = (int) $value;
                }
            }
        } else {
            if (is_bool($item)) {
                $item = (int) $item;
            }
        }
        return $item;
    }
    /**
     * Quote a string so it can be safely used as a table or column name
     *
     * Delimiting style depends on which database driver is being used.
     *
     * NOTE: just because you CAN use delimited identifiers doesn't mean
     * you SHOULD use them.  In general, they end up causing way more
     * problems than they solve.
     *
     * Portability is broken by using the following characters inside
     * delimited identifiers:
     *   + backtick (<kbd>`</kbd>) -- due to MySQL
     *   + double quote (<kbd>"</kbd>) -- due to Oracle
     *   + brackets (<kbd>[</kbd> or <kbd>]</kbd>) -- due to Access
     *
     * Delimited identifiers are known to generally work correctly under
     * the following drivers:
     *   + mssql
     *   + mysql
     *   + mysqli
     *   + oci8
     *   + pgsql
     *   + sqlite
     *
     * InterBase doesn't seem to be able to use delimited identifiers
     * via PHP 4.  They work fine under PHP 5.
     *
     * @param string $str           identifier name to be quoted
     * @param bool $checkOption     check the 'quote_identifier' option
     *
     * @return string               quoted identifier string
     */
    public function quoteIdentifier($str, $checkOption = true)
    {
        if ($checkOption && ! $this->conn->getAttribute(Doctrine::ATTR_QUOTE_IDENTIFIER)) {
            return $str;
        }
        $tmp = $this->conn->identifier_quoting;
        $str = str_replace($tmp['end'],
                           $tmp['escape'] .
                           $tmp['end'], $str);

        return $tmp['start'] . $str . $tmp['end'];
    }
    /**
     * quote
     * quotes given input parameter
     *
     * @param mixed $input      parameter to be quoted
     * @param string $type
     * @return mixed
     */
    public function quote($input, $type = null)
    {
        if ($type == null) {
            $type = gettype($input);
        }
        switch ($type) {
            case 'integer':
            case 'enum':
            case 'boolean':
            case 'double':
            case 'float':
            case 'bool':
            case 'int':
                return $input;
            case 'array':
            case 'object':
                $input = serialize($input);
            case 'string':
            case 'char':
            case 'varchar':
            case 'text':
            case 'gzip':
            case 'blob':
            case 'clob':
                $this->conn->connect();

                return $this->conn->getDbh()->quote($input);
        }
    }
    /**
     * Removes any formatting in an sequence name using the 'seqname_format' option
     *
     * @param string $sqn string that containts name of a potential sequence
     * @return string name of the sequence with possible formatting removed
     */
    public function fixSequenceName($sqn)
    {
        $seqPattern = '/^'.preg_replace('/%s/', '([a-z0-9_]+)',  $this->conn->getAttribute(Doctrine::ATTR_SEQNAME_FORMAT)).'$/i';
        $seqName    = preg_replace($seqPattern, '\\1', $sqn);

        if ($seqName && ! strcasecmp($sqn, $this->getSequenceName($seqName))) {
            return $seqName;
        }
        return $sqn;
    }
    /**
     * Removes any formatting in an index name using the 'idxname_format' option
     *
     * @param string $idx string that containts name of anl index
     * @return string name of the index with possible formatting removed
     */
    public function fixIndexName($idx)
    {
        $indexPattern   = '/^'.preg_replace('/%s/', '([a-z0-9_]+)', $this->conn->getAttribute(Doctrine::ATTR_IDXNAME_FORMAT)).'$/i';
        $indexName      = preg_replace($indexPattern, '\\1', $idx);
        if ($indexName && ! strcasecmp($idx, $this->getIndexName($indexName))) {
            return $indexName;
        }
        return $idx;
    }
    /**
     * adds sequence name formatting to a sequence name
     *
     * @param string    name of the sequence
     * @return string   formatted sequence name
     */
    public function getSequenceName($sqn)
    {
        return sprintf($this->conn->getAttribute(Doctrine::ATTR_SEQNAME_FORMAT),
            preg_replace('/[^a-z0-9_\$.]/i', '_', $sqn));
    }
    /**
     * adds index name formatting to a index name
     *
     * @param string    name of the index
     * @return string   formatted index name
     */
    public function getIndexName($idx)
    {
        return sprintf($this->conn->getAttribute(Doctrine::ATTR_IDXNAME_FORMAT),
                preg_replace('/[^a-z0-9_\$]/i', '_', $idx));
    }
}

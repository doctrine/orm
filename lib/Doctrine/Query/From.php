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
Doctrine::autoload("Doctrine_Query_Part");
/**
 * Doctrine_Query_From
 *
 * @package     Doctrine
 * @subpackage  Query
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Query_From extends Doctrine_Query_Part
{
    /**
     * DQL FROM PARSER
     * parses the from part of the query string
     *
     * @param string $str
     * @return void
     */
    public function parse($str)
    {
        $str = trim($str);
        $parts = Doctrine_Tokenizer::bracketExplode($str, 'JOIN');

        $operator = false;

        switch (trim($parts[0])) {
            case 'INNER':
                $operator = ':';
            case 'LEFT':
                array_shift($parts);
            break;
        }


        $last = '';

        foreach ($parts as $k => $part) {
            $part = trim($part);

            if (empty($part)) {
                continue;
            }

            $e    = explode(' ', $part);

            if (end($e) == 'INNER' || end($e) == 'LEFT') {
                $last = array_pop($e);
            }
            $part = implode(' ', $e);

            foreach (Doctrine_Tokenizer::bracketExplode($part, ',') as $reference) {
                $reference = trim($reference);
                $e = explode(' ', $reference);
                $e2 = explode('.', $e[0]);

                if ($operator) {
                    $e[0] = array_shift($e2) . $operator . implode('.', $e2);
                }

                $table = $this->query->load(implode(' ', $e));
            }

            $operator = ($last == 'INNER') ? ':' : '.';
        }
        return null;
    }
}
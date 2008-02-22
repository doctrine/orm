<?php
/*
 *  $Id: Date.php 2367 2007-09-02 20:00:27Z zYne $
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
 * <http://www.phpdoctrine.org>.
 */

/**
 * Doctrine_Validator_Past
 *
 * @package     Doctrine
 * @subpackage  Validator
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 * @author      Roman Borschel <roman@code-factory.org>
 */
class Doctrine_Validator_Past
{
    /**
     * checks if the given value is a valid date in the past.
     *
     * @param mixed $value
     * @return boolean
     */
    public function validate($value)
    {
        if ($value === null) {
            return true;
        }
        $e = explode('-', $value);

        if (count($e) !== 3) {
            return false;
        }
        
        if (is_array($this->args) && isset($this->args['timezone'])) {
            switch (strtolower($this->args['timezone'])) {
                case 'gmt':
                    $now = gmdate("U") - date("Z");
                    break;
                default:
                    $now = getdate();
                    break;
            }
        } else {
            $now = getdate();
        }
        
        if ($now['year'] < $e[0]) {
            return false;
        } else if ($now['year'] == $e[0]) {
            if ($now['mon'] < $e[1]) {
                return false;
            } else if ($now['mon'] == $e[1]) {
                return $now['mday'] > $e[2];
            } else {
                return true;
            }
        } else {
            return true;
        }
    }
}
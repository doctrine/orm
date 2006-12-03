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
/**
 * Doctrine_DataDict
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>       
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 */
class Doctrine_DataDict extends Doctrine_Connection_Module {
    /**
     * Obtain an array of changes that may need to applied
     *
     * @param array $current new definition
     * @param array  $previous old definition
     * @return array  containing all changes that will need to be applied
     */
    public function compareDefinition($current, $previous) {
        $type = !empty($current['type']) ? $current['type'] : null;

        if (!method_exists($this, "_compare{$type}Definition")) {
            return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'type "'.$current['type'].'" is not yet supported', __FUNCTION__);
        }

        if (empty($previous['type']) || $previous['type'] != $type) {
            return $current;
        }

        $change = $this->{"_compare{$type}Definition"}($current, $previous);

        if ($previous['type'] != $type) {
            $change['type'] = true;
        }

        $previous_notnull = !empty($previous['notnull']) ? $previous['notnull'] : false;
        $notnull = !empty($current['notnull']) ? $current['notnull'] : false;
        if ($previous_notnull != $notnull) {
            $change['notnull'] = true;
        }

        $previous_default = array_key_exists('default', $previous) ? $previous['default'] :
            ($previous_notnull ? '' : null);
        $default = array_key_exists('default', $current) ? $current['default'] :
            ($notnull ? '' : null);
        if ($previous_default !== $default) {
            $change['default'] = true;
        }

        return $change;
    }
}

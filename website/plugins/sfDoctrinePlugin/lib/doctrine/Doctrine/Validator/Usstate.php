<?php
/*
 *  $Id: Usstate.php 1080 2007-02-10 18:17:08Z romanb $
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
 * Doctrine_Validator_Usstate
 *
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1080 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Validator_Usstate
{
    private static $states = array (
                "AK" =>	true,
                "AL" => true,
                "AR" => true,
                "AZ" => true,
                "CA" => true,
                "CO" => true,
                "CT" => true,
                "DC" => true,
                "DE" => true,
                "FL" => true,
                "GA" => true,
                "HI" => true,
                "IA" => true,
                "ID" => true,
                "IL" => true,
                "IN" => true,
                "KS" => true,
                "KY" => true,
                "LA" => true,
                "MA" => true,
                "MD" => true,
                "ME" => true,
                "MI" => true,
                "MN" => true,
                "MO" => true,
                "MS" => true,
                "MT" => true,
                "NC" => true,
                "ND" => true,
                "NE" => true,
                "NH" => true,
                "NJ" => true,
                "NM" => true,
                "NV" => true,
                "NY" => true,
                "OH" => true,
                "OK" => true,
                "OR" => true,
                "PA" => true,
                "PR" => true,
                "RI" => true,
                "SC" => true,
                "SD" => true,
                "TN" => true,
                "TX" => true,
                "UT" => true,
                "VA" => true,
                "VI" => true,
                "VT" => true,
                "WA" => true,
                "WI" => true,
                "WV" =>	true,
                "WY" =>	true
            );
    public function getStates()
    {
        return self::$states;
    }
    /**
     * @param Doctrine_Record $record
     * @param string $key
     * @param mixed $value
     * @param string $args
     * @return boolean
     */
    public function validate(Doctrine_Record $record, $key, $value, $args)
    {
        return isset(self::$states[$value]);
    }
}

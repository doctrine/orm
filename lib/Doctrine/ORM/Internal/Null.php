<?php
/*
 *  $Id: Null.php 4723 2008-08-01 18:46:14Z romanb $
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

#namespace Doctrine\ORM\Internal;

/**
 * Null class representing a null value that has been fetched from
 * the database or a fetched, empty association. This is for internal use only.
 * User code should never deal with this null object.
 * 
 * Semantics are as follows:
 * 
 * Regular PHP null : Value is undefined. When a field with that value is accessed
 *                    and lazy loading is used the database is queried.
 * 
 * Null object: Null valued of a field or empty association that has already been loaded.
 *              On access, the database is not queried. 
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision: 4723 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @todo No longer needed?
 */
// static initializer
Doctrine_ORM_Internal_Null::$INSTANCE = new Doctrine_ORM_Internal_Null();
final class Doctrine_ORM_Internal_Null
{
    public static $INSTANCE;
    public function __construct() {}
    
    public static function getInstance()
    {
        return self::$INSTANCE;
    }
    
    public function exists()
    {
        return false;    
    }
    public function __toString()
    {
        return '';
    }
}
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
 * <http://www.phpdoctrine.org>.
 */

#namespace Doctrine::ORM::Exceptions;

/**
 * Doctrine_Entity_Exception
 *
 * @package     Doctrine
 * @subpackage  Entity
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class Doctrine_ORM_Exceptions_EntityException extends Doctrine_ORM_Exceptions_ORMException
{
    public static function unknownField($field)
    {
        return new self("Undefined field: '$field'.");    
    }
    
    public static function invalidValueForOneToManyReference()
    {
        return new self("Invalid value. The value of a reference in a OneToMany "
                . "association must be a Collection.");
    }
    
    public static function invalidValueForOneToOneReference()
    {
        return new self("Invalid value. The value of a reference in a OneToOne "
                . "association must be an Entity.");
    }
    
    public static function invalidValueForManyToManyReference()
    {
        return new self("Invalid value. The value of a reference in a ManyToMany "
                . "association must be a Collection.");
    }
    
    public static function invalidField($field)
    {
        return new self("Invalid field: '$field'.");
    }
}
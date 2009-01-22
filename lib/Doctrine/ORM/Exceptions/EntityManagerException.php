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

namespace Doctrine\ORM\Exceptions;

/**
 * Doctrine_EntityManager_Exception
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class EntityManagerException extends \Doctrine\Common\DoctrineException
{
    public static function invalidFlushMode()
    {
        return new self("Invalid flush mode.");
    }
    
    public static function noEntityManagerAvailable()
    {
        return new self("No EntityManager available.");
    }
    
    public static function entityAlreadyBound($entityName)
    {
        return new self("The entity '$entityName' is already bound.");
    }
    
    public static function noManagerWithName($emName)
    {
        return new self("EntityManager named '$emName' not found.");
    }
    
    public static function unknownAttribute($name)
    {
        return new self("Unknown EntityManager attribute '$name'.");
    }
}
<?php
/*
 *  $Id: Schema.php 6876 2009-12-06 23:11:35Z beberlei $
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

namespace Doctrine\DBAL\Schema;

/**
 * Configuration for a Schema
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 */
class SchemaConfig
{
    /**
     * @var bool
     */
    protected $_hasExplicitForeignKeyIndexes = false;

    /**
     * @var int
     */
    protected $_maxIdentifierLength = 63;

    /**
     * @return bool
     */
    public function hasExplicitForeignKeyIndexes()
    {
        return $this->_hasExplicitForeignKeyIndexes;
    }

    /**
     * @param bool $flag
     */
    public function setExplicitForeignKeyIndexes($flag)
    {
        $this->_hasExplicitForeignKeyIndexes = (bool)$flag;
    }

    /**
     * @param int $length
     */
    public function setMaxIdentifierLength($length)
    {
        $this->_maxIdentifierLength = (int)$length;
    }

    /**
     * @return int
     */
    public function getMaxIdentifierLength()
    {
        return $this->_maxIdentifierLength;
    }
}
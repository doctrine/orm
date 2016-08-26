<?php
/*
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
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Mapping;

class JoinColumnMetadata extends ColumnMetadata
{
    /** @var string */
    private $referencedColumnName;

    /** @var string */
    private $aliasedName;

    /** @var boolean */
    protected $nullable = true;

    /** @var string */
    private $onDelete;

    /**
     * @return string
     */
    public function getReferencedColumnName()
    {
        return $this->referencedColumnName;
    }

    /**
     * @param string $referencedColumnName
     */
    public function setReferencedColumnName($referencedColumnName)
    {
        $this->referencedColumnName = $referencedColumnName;
    }

    /**
     * @return string
     */
    public function getAliasedName()
    {
        return $this->aliasedName;
    }

    /**
     * @param string $aliasedName
     */
    public function setAliasedName($aliasedName)
    {
        $this->aliasedName = $aliasedName;
    }

    /**
     * @return string
     */
    public function getOnDelete()
    {
        return $this->onDelete;
    }

    /**
     * @param string $onDelete
     */
    public function setOnDelete($onDelete)
    {
        $this->onDelete = $onDelete ? strtoupper($onDelete) : $onDelete;
    }

    public function isOnDeleteCascade()
    {
        return $this->onDelete === 'CASCADE';
    }
}

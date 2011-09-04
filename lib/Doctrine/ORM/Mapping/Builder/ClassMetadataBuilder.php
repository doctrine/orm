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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Builder Object for ClassMetadata
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       2.0
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 */
class ClassMetadataBuilder
{
    /**
     * @var ClassMetadata
     */
    private $cm;

    public function __construct(ClassMetadata $cm)
    {
        $this->cm = $cm;
    }

    public function setMappedSuperClass()
    {
        $this->cm->isMappedSuperclass = true;
    }

    public function setCustomRepositoryClass($repositoryClassName)
    {
        $this->cm->setCustomRepositoryClass($repositoryClassName);
    }

    public function setReadOnly()
    {
        $this->cm->markReadOnly();
    }

    public function setTable($name)
    {
        $this->cm->setPrimaryTable(array('name' => $name));
    }

    public function addIndex(array $columns, $name)
    {
        if (!isset($this->cm->table['indexes'])) {
            $this->cm->table['indexes'] = array();
        }
        $this->cm->table['indexes'][$name] = array('columns' => $columns);
    }

    public function addUniqueConstraint(array $columns, $name)
    {
        if (!isset($this->cm->table['uniqueConstraints'])) {
            $this->cm->table['uniqueConstraints'] = array();
        }
        $this->cm->table['uniqueConstraints'][$name] = array('columns' => $columns);
    }
}
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

namespace Doctrine\ORM\Tools\Event;

use Doctrine\Common\EventArgs;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Event Args used for the Events::postGenerateSchemaTable event.
 *
 * @link        www.doctrine-project.com
 */
class GenerateSchemaTableEventArgs extends EventArgs
{
    /** @var ClassMetadata */
    private $classMetadata;

    /** @var Schema */
    private $schema;

    /** @var Table */
    private $classTable;

    public function __construct(ClassMetadata $classMetadata, Schema $schema, Table $classTable)
    {
        $this->classMetadata = $classMetadata;
        $this->schema        = $schema;
        $this->classTable    = $classTable;
    }

    /**
     * @return ClassMetadata
     */
    public function getClassMetadata()
    {
        return $this->classMetadata;
    }

    /**
     * @return Schema
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * @return Table
     */
    public function getClassTable()
    {
        return $this->classTable;
    }
}

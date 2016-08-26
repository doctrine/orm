<?php

declare(strict_types = 1);

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

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Mapping\VersionFieldMetadata;

class VersionFieldMetadataBuilder extends FieldMetadataBuilder
{
    /** @var string */
    protected $name = 'version';

    /**
     * {@inheritdoc}
     *
     * @throws MappingException
     */
    public function withType(Type $type)
    {
        $allowedTypeList = ['integer', 'bigint', 'smallint', 'datetime'];
        $typeName        = $type->getName();

        if (! in_array($typeName, $allowedTypeList)) {
            throw MappingException::unsupportedOptimisticLockingType($typeName);
        }

        $this->type = $type;

        return $this;
    }

    /**
     * @return VersionFieldMetadata
     */
    public function build()
    {
        if (! isset($this->options['default'])) {
            $this->options['default'] = $this->resolveDefaultValue();
        }

        return parent::build();
    }

    /**
     * @return VersionFieldMetadata
     */
    protected function createMetadataObject()
    {
        return new VersionFieldMetadata($this->name); // new VersionFieldMetadata($this->name, $this->columnName, $this->type);
    }

    /**
     * @return int|string
     */
    private function resolveDefaultValue()
    {
        switch ($this->type->getName()) {
            case Type::DATETIME:
                return 'CURRENT_TIMESTAMP';

            default:
                return 1;
        }
    }
}

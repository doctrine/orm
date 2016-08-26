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

use Doctrine\ORM\Mapping\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\FieldMetadata;
use Doctrine\ORM\Mapping\NamingStrategy;

class FieldMetadataBuilder extends ColumnMetadataBuilder
{
    /** @var string */
    protected $name;

    /** @var  */
    private $namingStrategy;

    public function __construct(NamingStrategy $namingStrategy = null)
    {
        parent::__construct();

        $this->namingStrategy = $namingStrategy ?: new DefaultNamingStrategy();
    }

    /**
     * @param string $name
     *
     * @return self
     */
    public function withName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return FieldMetadata
     */
    public function build()
    {
        if (empty($this->columnName)) {
            $this->columnName = $this->namingStrategy->propertyToColumnName($this->name);
        }

        return parent::build();
    }

    /**
     * @return FieldMetadata
     */
    protected function createMetadataObject()
    {
        return new FieldMetadata($this->name); // new FieldMetadata($this->name, $this->columnName, $this->type);
    }
}
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

namespace Doctrine\ORM\Mapping\Builder;

/**
 * OneToMany Association Builder
 *
 * @link        www.doctrine-project.com
 */
class OneToManyAssociationBuilder extends AssociationBuilder
{
    /**
     * @psalm-param array<string, string> $fieldNames
     *
     * @return static
     */
    public function setOrderBy(array $fieldNames)
    {
        $this->mapping['orderBy'] = $fieldNames;

        return $this;
    }

    /**
     * @param string $fieldName
     *
     * @return static
     */
    public function setIndexBy($fieldName)
    {
        $this->mapping['indexBy'] = $fieldName;

        return $this;
    }

    /**
     * @return ClassMetadataBuilder
     */
    public function build()
    {
        $mapping = $this->mapping;
        if ($this->joinColumns) {
            $mapping['joinColumns'] = $this->joinColumns;
        }

        $cm = $this->builder->getClassMetadata();
        $cm->mapOneToMany($mapping);

        return $this->builder;
    }
}

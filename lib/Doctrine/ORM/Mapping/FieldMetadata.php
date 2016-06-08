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

use Doctrine\Common\Persistence\Mapping\ReflectionService;
use Doctrine\DBAL\Types\Type;

class FieldMetadata extends ColumnMetadata implements Property
{
    /**
     * @var ClassMetadata
     */
    private $declaringClass;

    /**
     * @var ClassMetadata
     */
    private $currentClass;

    /**
     * @var \ReflectionProperty
     */
    private $reflection;

    /**
     * @var string
     */
    private $name;

    /**
     * {@inheritdoc}
     */
    public function getDeclaringClass()
    {
        return $this->declaringClass;
    }

    /**
     * @param ClassMetadata $declaringClass
     */
    public function setDeclaringClass(ClassMetadata $declaringClass)
    {
        $this->declaringClass = $declaringClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentClass()
    {
        return $this->currentClass;
    }

    /**
     * @param ClassMetadata $currentClass
     */
    public function setCurrentClass(ClassMetadata $currentClass)
    {
        $this->currentClass = $currentClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function isInherited()
    {
        return $this->declaringClass !== $this->currentClass;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($object, $value)
    {
        $this->reflection->setValue($object, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($object)
    {
        return $this->reflection->getValue($object);
    }

    /**
     * {@inheritdoc}
     */
    public function isAssociation()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isField()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function wakeupReflection(ReflectionService $reflectionService)
    {
        $this->reflection = $reflectionService->getAccessibleProperty(
            $this->getDeclaringClass()->name,
            $this->name
        );
    }

    /**
     * @return array
     */
    public function getMapping()
    {
        return [
            'declaringClass'   => $this->declaringClass->name,
            'currentClass'     => $this->currentClass->name,
            'tableName'        => $this->tableName,
            'columnName'       => $this->columnName,
            'columnDefinition' => $this->columnDefinition,
            'length'           => $this->length,
            'scale'            => $this->scale,
            'precision'        => $this->precision,
            'options'          => $this->options,
            'id'               => $this->primaryKey,
            'nullable'         => $this->nullable,
            'unique'           => $this->unique,
        ];
    }
}
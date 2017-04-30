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

namespace Doctrine\ORM\Mapping\Factory;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\ReflectionService;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Class metadata driver works as a middle layer between MappingDriver and ClassMetadataFactory.
 *
 * @todo guilhermeblanco Remove this class once MappingDriver gets properly updated.
 *
 * @package Doctrine\ORM\Mapping\Factory
 * @since 3.0
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class ClassMetadataDriver
{
    /**
     * @var MappingDriver
     */
    private $mappingDriver;

    /**
     * @var NamingStrategy
     */
    protected $namingStrategy;

    public function __construct(MappingDriver $mappingDriver, NamingStrategy $namingStrategy)
    {
        $this->mappingDriver  = $mappingDriver;
        $this->namingStrategy = $namingStrategy;
    }

    /**
     * @return array
     */
    public function getAllClassMetadata() : array
    {
        return $this->mappingDriver->getAllClassNames();
    }

    /**
     * @param string             $className
     * @param ClassMetadata|null $parent
     *
     * @return ClassMetadata
     */
    public function getClassMetadata(string $className, ?ClassMetadata $parent)
    {
        $builder = $this->mappingDriver->loadMetadataForClass($className, $parent);

        return $builder->build($this->namingStrategy);
    }

    /**
     * @param string $className
     *
     * @return bool
     */
    public function hasClassMetadata(string $className) : bool
    {
        return $this->mappingDriver->isTransient($className);
    }
}
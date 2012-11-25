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
namespace Doctrine\ORM\Internal\IdentityMap;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;

class DerivedKeyHashStrategy implements IdentifierHashStrategy
{
    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadata
     */
    private $class;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    public function __construct(ClassMetadata $class, EntityManager $entityManager)
    {
        $this->class         = $class;
        $this->entityManager = $entityManager;
    }

    public function getHash(array $identifier)
    {
        $class = $this->class;
        $em = $this->entityManager;
        return implode(
            ' ',
            array_map(function ($fieldName) use ($identifier, $class, $em) {

                    if (is_object($identifier[$fieldName]) && ! $em->getMetadataFactory()->isTransient($identifier[$fieldName])) {
                        $class = $em->getClassMetadata(get_class($identifier[$fieldName]));
                        $id = $em->getUnitOfWork()->getEntityIdentifier($identifier[$fieldName]);
                        return $em->getUnitOfWork()->getHashForEntityIdentifier($class, $id);
                    }

                    return $identifier[$fieldName];

            }, $class->identifier)
        );
    }
}


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


namespace Doctrine\ORM\Utility;

use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;

/**
 * The IdentifierFlattener utility now houses some of the identifier manipulation logic from unit of work, so that it
 * can be re-used elsewhere.
 *
 * @since       2.5
 * @author      Rob Caiger <rob@clocal.co.uk>
 */
final class IdentifierFlattener
{
    /**
     * The UnitOfWork used to coordinate object-level transactions.
     *
     * @var \Doctrine\ORM\UnitOfWork
     */
    private $unitOfWork;

    /**
     * The metadata factory, used to retrieve the ORM metadata of entity classes.
     *
     * @var \Doctrine\Common\Persistence\Mapping\ClassMetadataFactory
     */
    private $metadataFactory;

    /**
     * Initializes a new IdentifierFlattener instance, bound to the given EntityManager.
     *
     * @param \Doctrine\ORM\UnitOfWork $unitOfWork
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadataFactory $metadataFactory
     */
    public function __construct(UnitOfWork $unitOfWork, ClassMetadataFactory $metadataFactory)
    {
        $this->unitOfWork = $unitOfWork;
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * convert foreign identifiers into scalar foreign key values to avoid object to string conversion failures.
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadata $class
     * @param array $id
     * @return array
     */
    public function flattenIdentifier(ClassMetadata $class, array $id)
    {
        $flatId = array();

        foreach ($id as $idField => $idValue) {
            if (isset($class->associationMappings[$idField]) && is_object($idValue)) {
                $targetClassMetadata = $this->metadataFactory->getMetadataFor(
                    $class->associationMappings[$idField]['targetEntity']
                );

                $associatedId = $this->unitOfWork->getEntityIdentifier($idValue);

                $flatId[$idField] = $associatedId[$targetClassMetadata->identifier[0]];
            } else {
                $flatId[$idField] = $idValue;
            }
        }

        return $flatId;
    }
}

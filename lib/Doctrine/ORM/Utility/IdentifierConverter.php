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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * The IdentifierConverter utility now houses some of the identifier manipulation logic from unit of work, so that it
 * can be re-used elsewhere.
 * @Note Not sure who the specific authors of some of this logic were, so to give appropriate credit due, I have
 * included all authors from the UnitOfWork class
 *
 * @since       2.4
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Jonathan Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 * @author      Rob Caiger <rob@clocal.co.uk>
 * @internal    This class contains highly performance-sensitive code.
 */
class IdentifierConverter
{
    /**
     * The EntityManager that "owns" this UnitOfWork instance.
     *
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * Initializes a new IdentifierConverter instance, bound to the given EntityManager.
     *
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * convert foreign identifiers into scalar foreign key values to avoid object to string conversion failures.
     *
     * @param ClassMetadata $class
     * @param array $id
     * @return array
     */
    public function flattenIdentifier($class, $id)
    {
        $flatId = array();

        foreach ($id as $idField => $idValue) {
            if (isset($class->associationMappings[$idField]) && is_object($idValue)) {
                $targetClassMetadata = $this->em->getClassMetadata($class->associationMappings[$idField]['targetEntity']);
                $associatedId        = $this->em->getUnitOfWork()->getEntityIdentifier($idValue);

                $flatId[$idField] = $associatedId[$targetClassMetadata->identifier[0]];
            } else {
                $flatId[$idField] = $idValue;
            }
        }

        return $flatId;
    }
}

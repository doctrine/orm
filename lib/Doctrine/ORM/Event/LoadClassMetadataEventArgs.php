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

namespace Doctrine\ORM\Event;

use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs as BaseLoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;

/**
 * Class that holds event arguments for a loadMetadata event.
 *
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @since  2.0
 */
class LoadClassMetadataEventArgs extends BaseLoadClassMetadataEventArgs
{
    /**
     * @param ClassMetadata $classMetadata
     * @param EntityManager $entityManager
     */
    function __construct(ClassMetadata $classMetadata, EntityManager $entityManager)
    {
        /*
        We use our own constructor here to enforce type-hinting requirements,
        since both inputs are specialized subclasses compared to what the super-
        class is willing to accept.

        In particular, we want to have EntityManager rather than ObjectManager.
        */
        parent::__construct($classMetadata, $entityManager);
    }

    /**
     * Retrieve associated EntityManager.
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        $em = $this->getObjectManager();
        assert($em instanceof EntityManager);
        return $em;
    }

    /**
     * Retrieves the associated ClassMetadata.
     *
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    public function getClassMetadata()
    {
        return parent::getClassMetadata();
    }
}

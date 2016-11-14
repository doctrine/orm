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

namespace Doctrine\ORM\Id;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\IdGeneratorInterface;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractIdGenerator implements IdGeneratorInterface
{
    /**
     * {@inheritDoc}
     *
     * @param EntityManager $em
     * @param \Doctrine\ORM\Mapping\Entity $entity
     * @return mixed
     */
    abstract public function generate(EntityManager $em, $entity);

    public final function generateId(EntityManagerInterface $em, $entity)
    {
        /* @var $id mixed */
        $id = null;

        if ($em instanceof EntityManager) {
            $id = $this->generate($em, $entity);

        } else {
            throw new ORMException(sprintf(
                "Tried to use non-doctrine entity-manager %s with old id-generator %s which is not supported! ".
                "This id-generator must be upgraded to use the %s to make it work with different entity-managers!",
                get_class($em),
                get_class($this),
                IdGeneratorInterface::class
            ));
        }

        return $id;
    }

    /**
     * {@inheritDoc}
     *
     * By default, this method returns FALSE. Generators that have this requirement
     * must override this method and return TRUE.
     *
     * @return boolean
     */
    public function isPostInsertGenerator()
    {
        return false;
    }
}

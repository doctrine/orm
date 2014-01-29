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

namespace Doctrine\ORM\Query\Filter;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Default create-a-filter implementation
 * @author Nico Schoenmaker <nschoenmaker@hostnet.nl>
 */
class DefaultFilterFactory implements FilterFactory
{
    /**
     * The EntityManager that "owns" this FilterFactory instance.
     *
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    public function setEntityManager(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @throws \RunTimeException
     * @return \Doctrine\ORM\EntityManagerInterface
     */
    private function getEntityManager()
    {
        if (! $this->em) {
            throw new \RunTimeException('I need an entity manager to continue!');
        }
        return $this->em;
    }

    private function getConfig()
    {
        return $this->getEntityManager()->getConfiguration();
    }

    /**
     * {@inheritDoc}
     */
    public function canCreate($name)
    {
        return null !== $this->getConfig()->getFilterClassName($name);
    }

    /**
     * {@inheritDoc}
     */
    public function createFilter($name)
    {
      if (null === $filterClass = $this->getConfig()->getFilterClassName($name)) {
        throw new \InvalidArgumentException("Filter '" . $name . "' does not exist.");
      }
      return new $filterClass($this->getEntityManager());
    }
}
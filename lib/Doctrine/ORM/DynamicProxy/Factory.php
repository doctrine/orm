<?php
/*
 *  $Id$
 *
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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\DynamicProxy;

use Doctrine\ORM\EntityManager;

/**
 * This Factory is used to create proxy objects for entities at runtime.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
class Factory
{
    private $_em;
    private $_generator;

    /**
	 * Initializes a new instance of the <tt>DynamicProxyGenerator</tt> class that is
	 * connected to the given <tt>EntityManager</tt> and stores proxy class files in
	 * the given cache directory.
	 *
	 * @param EntityManager $em
	 * @param Generator $generator
     */
    public function __construct(EntityManager $em, Generator $generator)
    {
        $this->_em = $em;
        $this->_generator = $generator;
    }

    /**
     * Gets a reference proxy instance.
     *
     * @param string $className
     * @param mixed $identifier
     * @return object
     */
    public function getReferenceProxy($className, $identifier)
    {
        $proxyClassName = $this->_generator->generateReferenceProxyClass($className);
        $entityPersister = $this->_em->getUnitOfWork()->getEntityPersister($className);
        return new $proxyClassName($entityPersister, $identifier);
    }

    /**
     * Gets an association proxy instance.
     */
    public function getAssociationProxy($owner, \Doctrine\ORM\Mapping\AssociationMapping $assoc)
    {
        throw new Exception("Not yet implemented.");
        $proxyClassName = str_replace('\\', '_', $assoc->getTargetEntityName()) . 'AProxy';
        if ( ! class_exists($proxyClassName, false)) {
            $this->_em->getMetadataFactory()->setMetadataFor(self::$_ns . $proxyClassName, $this->_em->getClassMetadata($assoc->getTargetEntityName()));
            $fileName = $this->_cacheDir . $proxyClassName . '.g.php';
            if ( ! file_exists($fileName)) {
                $this->_generateAssociationProxyClass($assoc->getTargetEntityName(), $proxyClassName, $fileName);
            }
            require $fileName;
        }
        $proxyClassName = '\\' . self::$_ns . $proxyClassName;
        
        return new $proxyClassName($this->_em, $assoc, $owner);
    }
}

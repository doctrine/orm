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

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver,
    Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Doctrine\ORM\Mapping\MappingException;

/**
 * The DriverChain allows you to add multiple other mapping drivers for
 * certain namespaces
 *
 * @since 2.0
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @author Roman Borschel <roman@code-factory.org>
 */
class DriverChain extends MappingDriverChain
{
    /**
     * The default driver
     *
     * @var MappingDriver
     */
    private $defaultDriver;

    /**
     * Get the default driver.
     *
     * @return MappingDriver|null
     */
    public function getDefaultDriver()
    {
        return $this->defaultDriver;
    }

    /**
     * Set the default driver.
     *
     * @param MappingDriver $driver
     */
    public function setDefaultDriver(MappingDriver $driver)
    {
        $this->defaultDriver = $driver;
    }

    /**
     * {@inheritDoc}
     * @throws MappingException
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        /* @var $driver MappingDriver */
        foreach ($this->getDrivers() as $namespace => $driver) {
            if (strpos($className, $namespace) === 0) {
                $driver->loadMetadataForClass($className, $metadata);
                return;
            }
        }

        if ($this->defaultDriver !== null) {
            $this->defaultDriver->loadMetadataForClass($className, $metadata);
            return;
        }

        throw MappingException::classIsNotAValidEntityOrMappedSuperClass($className);
    }

    /**
     * {@inheritDoc}
     */
    public function isTransient($className)
    {
        if (!parent::isTransient($className)) {
            return false;
        }

        if ($this->defaultDriver !== null) {
            return $this->defaultDriver->isTransient($className);
        }

        return true;
    }
}

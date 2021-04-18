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

namespace Doctrine\ORM\Cache;

/**
 * Cache regions configuration
 */
class RegionsConfiguration
{
    /** @var array<string,int> */
    private $lifetimes = [];

    /** @var array<string,int> */
    private $lockLifetimes = [];

    /** @var int */
    private $defaultLifetime;

    /** @var int */
    private $defaultLockLifetime;

    /**
     * @param int $defaultLifetime
     * @param int $defaultLockLifetime
     */
    public function __construct($defaultLifetime = 3600, $defaultLockLifetime = 60)
    {
        $this->defaultLifetime     = (int) $defaultLifetime;
        $this->defaultLockLifetime = (int) $defaultLockLifetime;
    }

    /**
     * @return int
     */
    public function getDefaultLifetime()
    {
        return $this->defaultLifetime;
    }

    /**
     * @param int $defaultLifetime
     *
     * @return void
     */
    public function setDefaultLifetime($defaultLifetime)
    {
        $this->defaultLifetime = (int) $defaultLifetime;
    }

    /**
     * @return int
     */
    public function getDefaultLockLifetime()
    {
        return $this->defaultLockLifetime;
    }

    /**
     * @param int $defaultLockLifetime
     *
     * @return void
     */
    public function setDefaultLockLifetime($defaultLockLifetime)
    {
        $this->defaultLockLifetime = (int) $defaultLockLifetime;
    }

    /**
     * @param string $regionName
     *
     * @return int
     */
    public function getLifetime($regionName)
    {
        return $this->lifetimes[$regionName] ?? $this->defaultLifetime;
    }

    /**
     * @param string $name
     * @param int    $lifetime
     *
     * @return void
     */
    public function setLifetime($name, $lifetime)
    {
        $this->lifetimes[$name] = (int) $lifetime;
    }

    /**
     * @param string $regionName
     *
     * @return int
     */
    public function getLockLifetime($regionName)
    {
        return $this->lockLifetimes[$regionName] ?? $this->defaultLockLifetime;
    }

    /**
     * @param string $name
     * @param int    $lifetime
     *
     * @return void
     */
    public function setLockLifetime($name, $lifetime)
    {
        $this->lockLifetimes[$name] = (int) $lifetime;
    }
}

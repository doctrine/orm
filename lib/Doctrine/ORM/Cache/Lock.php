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
 * Cache Lock
 *
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class Lock
{
    const LOCK_READ  = 1;
    const LOCK_WRITE = 2;

    /**
     * @var string
     */
    public $value;

    /**
     * @var integer
     */
    public $time;

    /**
     * @var integer
     */
    public $type;

    /**
     * @param string $value
     * @param integer $type
     * @param integer $time
     */
    public function __construct($value, $type, $time = null)
    {
        $this->value = $value;
        $this->type  = $type;
        $this->time  = $time ? : time();
    }

    /**
     * @return \Doctrine\ORM\Cache\Lock
     */
    public static function createLockWrite()
    {
        return new self(uniqid(time() . self::LOCK_WRITE), self::LOCK_WRITE);
    }

    /**
     * @return \Doctrine\ORM\Cache\Lock
     */
    public static function createLockRead()
    {
        return new self(uniqid(time() . self::LOCK_READ), self::LOCK_READ);
    }
}

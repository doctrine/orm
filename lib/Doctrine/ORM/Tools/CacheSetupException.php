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

namespace Doctrine\ORM\Tools;

use Doctrine\ORM\ORMException;
use Throwable;

/**
 * A custom exception for handling failures during cache configuration from Doctrine\ORM\Tools\Setup
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author Paul Court <g@rgoyle.com>
 */
class CacheSetupException extends ORMException
{
    /**
     * @param Throwable|null $previous
     * @return CacheSetupException
     */
    public static function autoSetupFailed(Throwable $previous = null)
    {
        $code = 0;
        if ($previous !== null) {
            $code = $previous->getCode();
        }
        return new self(''
            . 'An attempt to automatically create a cache instance failed! - The most '
            . 'likely cause is the presence of a supported extension (apcu, memcached or '
            . 'redis) but no matching service running locally on 127.0.0.1. The best way'
            . 'to fix this is to manually create a cache instance and pass it to the '
            . 'Doctrine\ORM\Tools\Setup method that you are trying to use.',
            $code,
            $previous
            );
    }
}

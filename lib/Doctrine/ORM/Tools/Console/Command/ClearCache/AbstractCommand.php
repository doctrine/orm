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

namespace Doctrine\ORM\Tools\Console\Command\ClearCache;

use Symfony\Component\Console\Command\Command;
use Doctrine\Common\Cache;

/**
 * AbstractCommand to clear cache of the various cache drivers.
 *
 * @link    www.doctrine-project.org
 * @since   2.5
 */
abstract class AbstractCommand extends Command
{
    /**
     * @param object $cacheDriver
     *
     * @throws \LogicException
     */
    protected function checkCacheDriverType($cacheDriver)
    {
        if ($cacheDriver instanceof Cache\ApcCache) {
            $name = 'APC';
        } elseif ($cacheDriver instanceof Cache\ApcuCache) {
            $name = 'APCu';
        } elseif ($cacheDriver instanceof Cache\XcacheCache) {
            $name = 'XCache';
        }

        if (isset($name)) {
            throw new \LogicException(sprintf(
                'Cannot clear %s Cache from Console, '
                    . 'its shared in the Webserver memory and not accessible from the CLI.',
                $name
            ));
        }
    }
}

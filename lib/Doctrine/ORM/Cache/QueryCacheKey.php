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

use Doctrine\ORM\Cache;

/**
 * A cache key that identifies a particular query.
 */
class QueryCacheKey extends CacheKey
{
    /**
     * READ-ONLY: Public only for performance reasons, it should be considered immutable.
     *
     * @var int Cache key lifetime
     */
    public $lifetime;

    /**
     * READ-ONLY: Public only for performance reasons, it should be considered immutable.
     *
     * @var int Cache mode (Doctrine\ORM\Cache::MODE_*)
     */
    public $cacheMode;

    /**
     * READ-ONLY: Public only for performance reasons, it should be considered immutable.
     *
     * @var TimestampCacheKey|null
     */
    public $timestampKey;

    /**
     * @param string $hash      Result cache id
     * @param int    $lifetime  Query lifetime
     * @param int    $cacheMode Query cache mode
     */
    public function __construct(
        $hash,
        $lifetime = 0,
        $cacheMode = Cache::MODE_NORMAL,
        ?TimestampCacheKey $timestampKey = null
    ) {
        $this->hash         = $hash;
        $this->lifetime     = $lifetime;
        $this->cacheMode    = $cacheMode;
        $this->timestampKey = $timestampKey;
    }
}

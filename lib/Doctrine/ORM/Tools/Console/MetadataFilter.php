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

namespace Doctrine\ORM\Tools\Console;

use ArrayIterator;
use Countable;
use Doctrine\Persistence\Mapping\ClassMetadata;
use FilterIterator;
use RuntimeException;

use function count;
use function iterator_to_array;
use function preg_match;
use function sprintf;

/**
 * Used by CLI Tools to restrict entity-based commands to given patterns.
 *
 * @link        www.doctrine-project.com
 */
class MetadataFilter extends FilterIterator implements Countable
{
    /** @var mixed[] */
    private $filter = [];

    /**
     * Filter Metadatas by one or more filter options.
     *
     * @param ClassMetadata[] $metadatas
     * @param string[]|string $filter
     *
     * @return ClassMetadata[]
     */
    public static function filter(array $metadatas, $filter)
    {
        $metadatas = new MetadataFilter(new ArrayIterator($metadatas), $filter);

        return iterator_to_array($metadatas);
    }

    /**
     * @param mixed[]|string $filter
     */
    public function __construct(ArrayIterator $metadata, $filter)
    {
        $this->filter = (array) $filter;

        parent::__construct($metadata);
    }

    /**
     * @return bool
     */
    public function accept()
    {
        if (count($this->filter) === 0) {
            return true;
        }

        $it       = $this->getInnerIterator();
        $metadata = $it->current();

        foreach ($this->filter as $filter) {
            $pregResult = preg_match("/$filter/", $metadata->name);

            if ($pregResult === false) {
                throw new RuntimeException(
                    sprintf("Error while evaluating regex '/%s/'.", $filter)
                );
            }

            if ($pregResult) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->getInnerIterator());
    }
}

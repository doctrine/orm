<?php

declare(strict_types = 1);

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

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ColumnMetadata;
use Doctrine\ORM\Mapping\LocalColumnMetadata;

abstract class LocalColumnMetadataBuilder extends ColumnMetadataBuilder
{
    /** @var int */
    protected $length = 255;

    /** @var int */
    protected $scale;

    /** @var int */
    protected $precision;

    /**
     * @param int $length
     *
     * @return self
     */
    public function withLength(int $length)
    {
        $this->length = $length;

        return $this;
    }

    /**
     * @param int $scale
     *
     * @return self
     */
    public function withScale(int $scale)
    {
        $this->scale = $scale;

        return $this;
    }

    /**
     * @param int $precision
     *
     * @return self
     */
    public function withPrecision(int $precision)
    {
        $this->precision = $precision;

        return $this;
    }

    /**
     * @return LocalColumnMetadata
     */
    public function build()
    {
        /** @var LocalColumnMetadata $localColumnMetadata */
        $localColumnMetadata = parent::build();

        if ($this->length !== null) {
            $localColumnMetadata->setLength($this->length);
        }

        if ($this->scale !== null) {
            $localColumnMetadata->setScale($this->scale);
        }

        if ($this->precision !== null) {
            $localColumnMetadata->setPrecision($this->precision);
        }

        return $localColumnMetadata;
    }
}
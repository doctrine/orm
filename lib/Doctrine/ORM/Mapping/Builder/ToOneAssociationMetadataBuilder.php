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

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\ORM\Mapping\JoinColumnMetadata;
use Doctrine\ORM\Mapping\ToOneAssociationMetadata;

abstract class ToOneAssociationMetadataBuilder extends AssociationMetadataBuilder
{
    /** @var array<JoinColumnMetadata> */
    private $joinColumns = [];

    /**
     * @param JoinColumnMetadata $joinColumn
     *
     * @return self
     */
    public function withJoinColumn(JoinColumnMetadata $joinColumn)
    {
        $this->joinColumns[] = $joinColumn;

        return $this;
    }

    /**
     * @return ToOneAssociationMetadata
     */
    public function build()
    {
        /** @var ToOneAssociationMetadata $associationMetadata */
        $associationMetadata = parent::build();

        $associationMetadata->setJoinColumns($this->joinColumns);

        return $associationMetadata;
    }
}
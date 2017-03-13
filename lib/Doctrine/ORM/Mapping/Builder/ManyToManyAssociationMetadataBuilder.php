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

use Doctrine\ORM\Mapping\JoinTableMetadata;
use Doctrine\ORM\Mapping\ManyToManyAssociationMetadata;

class ManyToManyAssociationMetadataBuilder extends ToManyAssociationMetadataBuilder
{
    /** @var null|JoinTableMetadata */
    private $joinTable;

    /**
     * @param null|JoinTableMetadata $joinTable
     *
     * @return self
     */
    public function withJoinTable(JoinTableMetadata $joinTable = null)
    {
        $this->joinTable = $joinTable;

        return $this;
    }

    /**
     * @return ManyToManyAssociationMetadata
     */
    public function build()
    {
        /** @var ManyToManyAssociationMetadata $associationMetadata */
        $associationMetadata = parent::build();

        $associationMetadata->setJoinTable($this->joinTable);

        return $associationMetadata;
    }

    /**
     * @return ManyToManyAssociationMetadata
     */
    protected function createMetadataObject()
    {
        return new ManyToManyAssociationMetadata($this->name);
    }
}
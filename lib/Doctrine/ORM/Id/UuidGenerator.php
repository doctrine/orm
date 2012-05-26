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

namespace Doctrine\ORM\Id;

use Serializable, Doctrine\ORM\EntityManager;

/**
 * Represents an ID generator that uses the database UUID expression
 *
 * @since 2.3
 * @author Maarten de Keizer <m.de.keizer@markei.nl>
 */
class UuidGenerator extends AbstractIdGenerator
{
    
    /**
     * Generates an ID for the given entity.
     *
     * @param Doctrine\ORM\EntityManager $em The EntityManager to user
     * @param object $entity
     * @return string The generated value.
     * @override
     */
    public function generate(EntityManager $em, $entity)
    {
        $conn = $em->getConnection();
        $sql = 'SELECT ' . $conn->getDatabasePlatform()->getGuidExpression();
        return $conn->query($sql)->fetchColumn(0);
    }
    
}

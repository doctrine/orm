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

namespace Doctrine\ORM\Query\Filter;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Responsible for creating filters
 *
 * Inject custom implementation into Configuration to override
 * @author Nico Schoenmaker <nschoenmaker@hostnet.nl>
 */
interface FilterFactory
{
    /**
     * Can I create a filter with the given name?
     * @param EntityManagerInterface $em
     * @param string $name
     * @return bool
     */
    public function canCreate(EntityManagerInterface $em, $name);

    /**
     * Creates a filter by that name
     * @param EntityManagerInterface $em
     * @param string $name
     * @throws FilterNotFoundException If filter with that name doesn't exist
     * @return SQLFilter
     */
    public function createFilter(EntityManagerInterface $em, $name);
}

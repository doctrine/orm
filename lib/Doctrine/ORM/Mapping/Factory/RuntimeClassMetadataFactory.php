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

namespace Doctrine\ORM\Mapping\Factory;

use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;

/**
 * RuntimeClassMetadataFactory is the ClassMetadata object creation factory that runs at
 * execution time, consuming pre-exising entity classes.
 *
 * @package Doctrine\ORM\Mapping\Factory
 * @since 3.0
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class RuntimeClassMetadataFactory extends AbstractClassMetadataFactory
{
    /** @var RuntimeReflectionService */
    private $reflectionService;

    /**
     * @return RuntimeReflectionService
     */
    protected function getReflectionService() : RuntimeReflectionService
    {
        if (! $this->reflectionService) {
            $this->reflectionService = new RuntimeReflectionService();
        }

        return $this->reflectionService;
    }
}

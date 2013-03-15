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

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
interface AccessProvider
{
    /**
     * Build an entity RegionAccess for the input entity.
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadata $metadata The entity metadata.
     *
     * @return \Doctrine\ORM\Cache\RegionAccess The built region access.
     *
     * @throws \Doctrine\ORM\Cache\CacheException Indicates problems building the region access.
     */
    public function buildEntityRegionAccessStrategy(ClassMetadata $metadata);

    /**
     * Build an collection RegionAccess for the input entity accociation.
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadata $metadata  The entity metadata.
     * @param string                              $fieldName The field name that represents the association.
     *
     * @return \Doctrine\ORM\Cache\RegionAccess The built region access.
     *
     * @throws \Doctrine\ORM\Cache\CacheException Indicates problems building the region access.
     */
    public function buildCollectionRegionAccessStrategy(ClassMetadata $metadata, $fieldName);
}

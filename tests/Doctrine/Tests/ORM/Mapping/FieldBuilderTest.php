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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

class FieldBuilderTest extends \Doctrine\Tests\OrmTestCase
{
    public function testCustomIdGeneratorCanBeSet()
    {
        $cmBuilder = new ClassMetadataBuilder(new ClassMetadataInfo('Doctrine\Tests\Models\CMS\CmsUser'));

        $fieldBuilder = $cmBuilder->createField('aField', 'string');

        $fieldBuilder->generatedValue('CUSTOM');
        $fieldBuilder->setCustomIdGenerator('stdClass');

        $fieldBuilder->build();

        $this->assertEquals(ClassMetadataInfo::GENERATOR_TYPE_CUSTOM, $cmBuilder->getClassMetadata()->generatorType);
        $this->assertEquals(['class' => 'stdClass'], $cmBuilder->getClassMetadata()->customGeneratorDefinition);
    }
}

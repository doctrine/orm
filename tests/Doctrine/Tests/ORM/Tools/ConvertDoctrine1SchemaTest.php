<?php
/*
 *  $Id$
 *
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

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\ORM\Tools\Export\ClassMetadataExporter,
    Doctrine\ORM\Tools\ConvertDoctrine1Schema;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Test case for converting a Doctrine 1 style schema to Doctrine 2 mapping files
 *
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class ConvertDoctrine1SchemaTest extends \Doctrine\Tests\OrmTestCase
{
    public function testTest()
    {
        $cme = new ClassMetadataExporter();
        $converter = new ConvertDoctrine1Schema(__DIR__ . '/doctrine1schema');

        $exporter = $cme->getExporter('yml', __DIR__ . '/convert');
        $exporter->setMetadatas($converter->getMetadatasFromSchema());
        $exporter->export();

        $this->assertTrue(file_exists(__DIR__ . '/convert/User.dcm.yml'));
        $this->assertTrue(file_exists(__DIR__ . '/convert/Profile.dcm.yml'));

        $cme->addMappingSource(__DIR__ . '/convert', 'yml');
        $metadatas = $cme->getMetadatasForMappingSources();

        $this->assertEquals(2, count($metadatas));
        $this->assertEquals('Profile', $metadatas['Profile']->name);
        $this->assertEquals('User', $metadatas['User']->name);
        $this->assertEquals(4, count($metadatas['Profile']->fieldMappings));
        $this->assertEquals(3, count($metadatas['User']->fieldMappings));

        $this->assertEquals('Profile', $metadatas['Profile']->associationMappings['User']->sourceEntityName);
        $this->assertEquals('User', $metadatas['Profile']->associationMappings['User']->targetEntityName);

        $this->assertEquals('username', $metadatas['User']->primaryTable['indexes']['username']['columns'][0]);

        unlink(__DIR__ . '/convert/User.dcm.yml');
        unlink(__DIR__ . '/convert/Profile.dcm.yml');
        rmdir(__DIR__ . '/convert');
    }
}
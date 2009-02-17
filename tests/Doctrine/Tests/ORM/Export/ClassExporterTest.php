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

namespace Doctrine\Tests\ORM\Export;

use Doctrine\ORM\Export\ClassExporter;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Test case for testing the ddl class exporter
 *
 * @package     Doctrine
 * @subpackage  Query
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 */
class ClassExporterTest extends \Doctrine\Tests\OrmTestCase
{
    public function testTest()
    {
        // DDL is platform dependant. We can inject the platform to test into the driver mock.
        $driver = new \Doctrine\Tests\Mocks\DriverMock;
        $conn = new \Doctrine\Tests\Mocks\ConnectionMock(array(), $driver);
        //$conn->setDatabasePlatform(new \Doctrine\DBAL\Platforms\SqlitePlatform());
        $conn->setDatabasePlatform(new \Doctrine\DBAL\Platforms\MySqlPlatform());

        $em = $this->_getTestEntityManager($conn);

        $classes = array(
            $em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsUser'),
            $em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsPhonenumber')
        );

        $exporter = new ClassExporter($em);
        $sql = $exporter->getExportClassesSql($classes);
        print_r($sql);
        
    }
}
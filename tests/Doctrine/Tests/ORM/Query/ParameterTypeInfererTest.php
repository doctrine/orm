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
namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\Query\ParameterTypeInferer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use PDO;

require_once __DIR__ . '/../../TestInit.php';

class ParameterTypeInfererTest extends \Doctrine\Tests\OrmTestCase
{

    public function providerParameterTypeInferer()
    {
         return array(
            array(1,                 Type::INTEGER),
            array("bar",             PDO::PARAM_STR),
            array("1",               PDO::PARAM_STR),
            array(new \DateTime,     Type::DATETIME),
            array(array(2),          Connection::PARAM_INT_ARRAY),
            array(array("foo"),      Connection::PARAM_STR_ARRAY),
            array(array("1","2"),    Connection::PARAM_STR_ARRAY),
            array(array(),           Connection::PARAM_STR_ARRAY),
            array(true,              Type::BOOLEAN),
        );
    }

    /**
     * @dataProvider providerParameterTypeInferer
     */

    public function testParameterTypeInferer($value, $expected)
    {
        $this->assertEquals($expected, ParameterTypeInferer::inferType($value));
    }
}

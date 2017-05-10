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

namespace Doctrine\Tests\ORM\Query\Expr;

use Doctrine\ORM\Query\Expr\Comparison;

/**
 * @author Benjamin Lazarecki <benjamin.lazarecki@gmail.com>
 */
class ComparisonTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider comparisonProvider
     */
    public function testThatComparisonCanHandleBoolean($left, $right, $expected)
    {
        $comparison = new Comparison($left, Comparison::EQ, $right);

        $this->assertSame($expected, $comparison->__toString());
    }

    public static function comparisonProvider()
    {
        return [
            [true, false, 'true = false'],
            ['true', 'false', 'true = false'],
            [1, 0, '1 = 0'],
            ['foo', 'bar', 'foo = bar'],
        ];
    }
}

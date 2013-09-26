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

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Generic\DateTimeModel;
use Doctrine\Common\Collections\Criteria;

/**
 * @author Josiah <josiah@jjs.id.au>
 */
class EntityRepositoryCriteriaTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('generic');
        parent::setUp();
    }

    public function tearDown()
    {
        if ($this->_em) {
            $this->_em->getConfiguration()->setEntityNamespaces(array());
        }
        parent::tearDown();
    }

    public function loadFixture()
    {
        $today = new DateTimeModel();
        $today->datetime =
        $today->date =
        $today->time =
            new \DateTime('today');
        $this->_em->persist($today);

        $tomorrow = new DateTimeModel();
        $tomorrow->datetime =
        $tomorrow->date =
        $tomorrow->time =
            new \DateTime('tomorrow');
        $this->_em->persist($tomorrow);

        $yesterday = new DateTimeModel();
        $yesterday->datetime =
        $yesterday->date =
        $yesterday->time =
            new \DateTime('yesterday');
        $this->_em->persist($yesterday);

        $this->_em->flush();

        unset($today);
        unset($tomorrow);
        unset($yesterday);

        $this->_em->clear();
    }

    public function testLteDateComparison()
    {
        $this->loadFixture();

        $repository = $this->_em->getRepository('Doctrine\Tests\Models\Generic\DateTimeModel');
        $dates = $repository->matching(new Criteria(
            Criteria::expr()->lte('datetime', new \DateTime('today'))
        ));

        $this->assertEquals(2, count($dates));
    }

    private function loadNullFieldFixtures()
    {
        $today = new DateTimeModel();
        $today->datetime =
        $today->date =
            new \DateTime('today');

        $this->_em->persist($today);

        $tomorrow = new DateTimeModel();
        $tomorrow->datetime =
        $tomorrow->date =
        $tomorrow->time =
            new \DateTime('tomorrow');
        $this->_em->persist($tomorrow);

        $this->_em->flush();
        $this->_em->clear();
    }

    public function testIsNullComparison()
    {
        $this->loadNullFieldFixtures();
        $repository = $this->_em->getRepository('Doctrine\Tests\Models\Generic\DateTimeModel');

        $dates = $repository->matching(new Criteria(
            Criteria::expr()->isNull('time')
        ));

        $this->assertEquals(1, count($dates));
    }

    public function testEqNullComparison()
    {
        $this->loadNullFieldFixtures();
        $repository = $this->_em->getRepository('Doctrine\Tests\Models\Generic\DateTimeModel');

        $dates = $repository->matching(new Criteria(
            Criteria::expr()->eq('time', null)
        ));

        $this->assertEquals(1, count($dates));
    }

    public function testNotEqNullComparison()
    {
        $this->loadNullFieldFixtures();
        $repository = $this->_em->getRepository('Doctrine\Tests\Models\Generic\DateTimeModel');

        $dates = $repository->matching(new Criteria(
            Criteria::expr()->neq('time', null)
        ));

        $this->assertEquals(1, count($dates));
    }
}

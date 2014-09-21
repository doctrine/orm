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
use Doctrine\Tests\Models\Tweet\Tweet;
use Doctrine\Tests\Models\Tweet\User;

/**
 * @author Josiah <josiah@jjs.id.au>
 */
class EntityRepositoryCriteriaTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('generic');
        $this->useModelSet('tweet');
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

    public function testCanCountWithoutLoadingCollection()
    {
        $this->loadFixture();
        $repository = $this->_em->getRepository('Doctrine\Tests\Models\Generic\DateTimeModel');

        $dates = $repository->matching(new Criteria());

        $this->assertFalse($dates->isInitialized());
        $this->assertCount(3, $dates);
        $this->assertFalse($dates->isInitialized());

        // Test it can work even with a constraint
        $dates = $repository->matching(new Criteria(
            Criteria::expr()->lte('datetime', new \DateTime('today'))
        ));

        $this->assertFalse($dates->isInitialized());
        $this->assertCount(2, $dates);
        $this->assertFalse($dates->isInitialized());

        // Trigger a loading, to make sure collection is initialized
        $date = $dates[0];
        $this->assertTrue($dates->isInitialized());
    }

    public function testCanContainsWithoutLoadingCollection()
    {
        $user = new User();
        $user->name = 'Marco';
        $this->_em->persist($user);
        $this->_em->flush();

        $tweet = new Tweet();
        $tweet->author = $user;
        $tweet->content = 'Criteria is awesome';
        $this->_em->persist($tweet);
        $this->_em->flush();

        $this->_em->clear();

        $criteria = new Criteria();
        $criteria->andWhere($criteria->expr()->contains('content', 'Criteria'));

        $user   = $this->_em->find('Doctrine\Tests\Models\Tweet\User', $user->id);
        $tweets = $user->tweets->matching($criteria);

        $this->assertInstanceOf('Doctrine\ORM\LazyCriteriaCollection', $tweets);
        $this->assertFalse($tweets->isInitialized());

        $tweets->contains($tweet);
        $this->assertTrue($tweets->contains($tweet));

        $this->assertFalse($tweets->isInitialized());
    }
}

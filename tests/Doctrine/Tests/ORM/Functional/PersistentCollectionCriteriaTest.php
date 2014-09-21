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
 * and is licensed under the MIT license.
 */

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Tests\Models\Tweet\Tweet;
use Doctrine\Tests\Models\Tweet\User;

/**
 * @author Michaël Gallego <mic.gallego@gmail.com>
 */
class PersistentCollectionCriteriaTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
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
        $author = new User();
        $author->name = 'ngal';
        $this->_em->persist($author);

        $tweet1 = new Tweet();
        $tweet1->content = 'Foo';
        $author->addTweet($tweet1);

        $tweet2 = new Tweet();
        $tweet2->content = 'Bar';
        $author->addTweet($tweet2);

        $this->_em->flush();

        unset($author);
        unset($tweet1);
        unset($tweet2);

        $this->_em->clear();
    }

    public function testCanCountWithoutLoadingPersistentCollection()
    {
        $this->loadFixture();
        $repository = $this->_em->getRepository('Doctrine\Tests\Models\Tweet\User');

        $user   = $repository->findOneBy(array('name' => 'ngal'));
        $tweets = $user->tweets->matching(new Criteria());

        $this->assertInstanceOf('Doctrine\ORM\LazyCriteriaCollection', $tweets);
        $this->assertFalse($tweets->isInitialized());
        $this->assertCount(2, $tweets);
        $this->assertFalse($tweets->isInitialized());

        // Make sure it works with constraints
        $tweets = $user->tweets->matching(new Criteria(
            Criteria::expr()->eq('content', 'Foo')
        ));

        $this->assertInstanceOf('Doctrine\ORM\LazyCriteriaCollection', $tweets);
        $this->assertFalse($tweets->isInitialized());
        $this->assertCount(1, $tweets);
        $this->assertFalse($tweets->isInitialized());
    }
}

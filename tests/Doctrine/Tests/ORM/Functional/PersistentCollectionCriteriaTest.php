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
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsComment;

/**
 * @author Michaël Gallego <mic.gallego@gmail.com>
 */
class PersistentCollectionCriteriaTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
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
        $article = new CmsArticle();
        $article->topic = 'Criteria is awesome';
        $article->text = 'foo';
        $this->_em->persist($article);

        $comment1 = new CmsComment();
        $comment1->topic = 'I agree';
        $comment1->text = 'bar';
        $this->_em->persist($comment1);
        $article->addComment($comment1);

        $comment2 = new CmsComment();
        $comment2->topic = 'I disagree';
        $comment2->text = 'baz';
        $this->_em->persist($comment2);
        $article->addComment($comment2);

        $this->_em->flush();

        unset($article);
        unset($comment1);
        unset($comment2);

        $this->_em->clear();
    }

    public function testCanCountWithoutLoadingPersistentCollection()
    {
        $this->loadFixture();
        $repository = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsArticle');

        $article  = $repository->findOneBy(array('topic' => 'Criteria is awesome'));
        $comments = $article->comments->matching(new Criteria());

        $this->assertInstanceOf('Doctrine\ORM\LazyCriteriaCollection', $comments);
        $this->assertFalse($comments->isInitialized());
        $this->assertCount(2, $comments);
        $this->assertFalse($comments->isInitialized());

        // Make sure it works with constraints
        $comments = $article->comments->matching(new Criteria(
            Criteria::expr()->eq('text', 'bar')
        ));

        $this->assertInstanceOf('Doctrine\ORM\LazyCriteriaCollection', $comments);
        $this->assertFalse($comments->isInitialized());
        $this->assertCount(1, $comments);
        $this->assertFalse($comments->isInitialized());
    }
}

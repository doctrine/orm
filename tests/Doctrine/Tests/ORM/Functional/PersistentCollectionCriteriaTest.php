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
use Doctrine\Tests\Models\Company\CompanyAuction;
use Doctrine\Tests\Models\Company\CompanyEmployee;
use Doctrine\Tests\Models\Company\CompanyFlexUltraContract;
use Doctrine\Tests\Models\Company\CompanyOrganization;

/**
 * @author Michaël Gallego <mic.gallego@gmail.com>
 */
class PersistentCollectionCriteriaTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('company');
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
        $companyOrganization = new CompanyOrganization();
        $this->_em->persist($companyOrganization);

        $event1 = new CompanyAuction();
        $event1->setData('Foo');
        $this->_em->persist($event1);
        $companyOrganization->addEvent($event1);

        $event2 = new CompanyAuction();
        $event2->setData('Bar');
        $this->_em->persist($event2);
        $companyOrganization->addEvent($event2);

        $this->_em->flush();

        unset($companyOrganization);
        unset($event1);
        unset($event2);

        $this->_em->clear();
    }

    public function testCanCountWithoutLoadingPersistentCollection()
    {
        $this->loadFixture();
        $repository = $this->_em->getRepository('Doctrine\Tests\Models\Company\CompanyOrganization');

        $organization = $repository->find(1);
        $events       = $organization->events->matching(new Criteria());

        $this->assertInstanceOf('Doctrine\ORM\LazyCriteriaCollection', $events);
        $this->assertFalse($events->isInitialized());
        $this->assertCount(2, $events);
        $this->assertFalse($events->isInitialized());

        // Make sure it works with constraints
        $events = $organization->events->matching(new Criteria(
            Criteria::expr()->eq('id', 2)
        ));

        $this->assertInstanceOf('Doctrine\ORM\LazyCriteriaCollection', $events);
        $this->assertFalse($events->isInitialized());
        $this->assertCount(1, $events);
        $this->assertFalse($events->isInitialized());
    }
}

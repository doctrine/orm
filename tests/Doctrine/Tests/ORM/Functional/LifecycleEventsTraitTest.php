<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\ORM\Mapping\ClassMetaData;
use Doctrine\Common\Cache\ArrayCache;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsComment;

use Doctrine\Tests\Models\Company\CompanyPerson;
use Doctrine\Tests\Models\Company\CompanyManager;
use Doctrine\Tests\Models\Company\CompanyEmployee;
use Doctrine\Tests\Models\Company\CompanyOrganization;
use Doctrine\Tests\Models\Company\CompanyAuction;

use Doctrine\Tests\Models\Company\CompanyFlexContract;
use Doctrine\Tests\Models\Company\CompanyFlexUltraContract;
use Doctrine\Tests\Models\Timestampable\User;

/**
 * Tests Lifecycle events from traits.
 *
 * @author Kinn Coelho Julião <kinncj@php.net>
 *
 * @group non-cacheable
 */
class LifecycleEventsTraitTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $userId, $userId2, $articleId, $articleId2;
    private $groupId, $groupId2;
    private $managerId, $managerId2, $contractId1, $contractId2;
    private $organizationId, $eventId1, $eventId2;

    public function setUp()
    {
        $this->useModelSet('timestampable_user');
        parent::setUp();
    }

    public function testShouldExecuteOnCreate()
    {
        $user = new User();

        $user->name = 'Joao';

        $this->_em->persist($user);

        $createDate = new \DateTime('now');

        $this->_em->flush();

        $this->assertEquals($user->getCreated(), $createDate);

        $user->name = 'Maria';

        $this->_em->persist($user);

        $updatedDate = new \DateTime('now');

        $this->_em->flush();

        $this->assertEquals($user->getUpdated(), $updatedDate);
    }
}
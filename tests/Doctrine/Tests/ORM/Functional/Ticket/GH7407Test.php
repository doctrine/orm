<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\UnitOfWork;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;
use ReflectionClass;

use function spl_object_id;

class GH7407Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('cms');

        parent::setUp();
    }

    public function testMergingEntitiesDoesNotCreateUnmanagedProxyReferences(): void
    {
        // 1. Create an article with a user; persist, flush and clear the entity manager
        $user           = new CmsUser();
        $user->username = 'Test';
        $user->name     = 'Test';
        $this->_em->persist($user);

        $article        = new CmsArticle();
        $article->topic = 'Test';
        $article->text  = 'Test';
        $article->setAuthor($user);
        $this->_em->persist($article);

        $this->_em->flush();
        $this->_em->clear();

        // 2. Merge the user object back in:
        // We get a new (different) entity object that represents the user instance
        // which is now (through this object instance) managed by the EM/UoW
        $mergedUser    = $this->_em->merge($user);
        $mergedUserOid = spl_object_id($mergedUser);

        // 3. Merge the article object back in,
        // the returned entity object is the article instance as it is managed by the EM/UoW
        $mergedArticle    = $this->_em->merge($article);
        $mergedArticleOid = spl_object_id($mergedArticle);

        self::assertSame($mergedUser, $mergedArticle->user, 'The $mergedArticle\'s #user property should hold the $mergedUser we obtained previously, since that\'s the only legitimate object instance representing the user from the UoW\'s point of view.');

        // Inspect internal UoW state
        $uow               = $this->_em->getUnitOfWork();
        $entityIdentifiers = $this->grabProperty('entityIdentifiers', $uow);
        $identityMap       = $this->grabProperty('identityMap', $uow);
        $entityStates      = $this->grabProperty('entityStates', $uow);

        self::assertCount(2, $entityIdentifiers, 'UoW#entityIdentifiers contains exactly two OID -> ID value mapping entries one for the article, one for the user object');
        self::assertArrayHasKey($mergedArticleOid, $entityIdentifiers);
        self::assertArrayHasKey($mergedUserOid, $entityIdentifiers);

        self::assertSame([
            $mergedUserOid => UnitOfWork::STATE_MANAGED,
            $mergedArticleOid => UnitOfWork::STATE_MANAGED,
        ], $entityStates, 'UoW#entityStates contains two OID -> state entries, one for the article, one for the user object');

        self::assertCount(2, $entityIdentifiers);
        self::assertArrayHasKey($mergedArticleOid, $entityIdentifiers);
        self::assertArrayHasKey($mergedUserOid, $entityIdentifiers);

        self::assertSame([
            CmsUser::class => [$user->id => $mergedUser],
            CmsArticle::class => [$article->id => $mergedArticle],
        ], $identityMap, 'The identity map contains exactly two objects, the article and the user.');
    }

    /** @return mixed */
    private function grabProperty(string $name, UnitOfWork $uow)
    {
        $reflection = new ReflectionClass($uow);
        $property   = $reflection->getProperty($name);
        $property->setAccessible(true);

        return $property->getValue($uow);
    }
}

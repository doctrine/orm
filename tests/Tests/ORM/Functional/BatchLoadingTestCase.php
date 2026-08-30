<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\BatchLoading\BatchLoadChild;
use Doctrine\Tests\Models\BatchLoading\BatchLoadOwner;
use Doctrine\Tests\Models\BatchLoading\BatchLoadProfile;
use Doctrine\Tests\Models\BatchLoading\BatchLoadTag;
use Doctrine\Tests\OrmFunctionalTestCase;

use function strlen;
use function substr;

/**
 * Fixture for batched and explicit association loading.
 *
 * Four owners, each with two children, two indexed children, an inverse
 * one-to-one profile and three tags - two of which are shared by every owner,
 * so that fanning join table rows out to several collections is covered.
 *
 * The inverse one-to-one is deliberate: loading it starts a nested hydration in
 * the middle of the outer one, which used to flush a deferred batch after a
 * single owner.
 */
abstract class BatchLoadingTestCase extends OrmFunctionalTestCase
{
    protected const OWNER_COUNT = 4;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            BatchLoadOwner::class,
            BatchLoadChild::class,
            BatchLoadTag::class,
            BatchLoadProfile::class,
        );

        $this->emptyDatabase();
        $this->createFixture();
    }

    private function emptyDatabase(): void
    {
        $connection = $this->_em->getConnection();
        $joinTable  = $this->_em->getClassMetadata(BatchLoadOwner::class)->associationMappings['tags']->joinTable->name;

        $connection->executeStatement('DELETE FROM ' . $joinTable);

        foreach ([BatchLoadChild::class, BatchLoadProfile::class, BatchLoadOwner::class, BatchLoadTag::class] as $entity) {
            $connection->executeStatement('DELETE FROM ' . $this->_em->getClassMetadata($entity)->getTableName());
        }
    }

    private function createFixture(): void
    {
        $shared = [];

        foreach (['alpha', 'beta'] as $name) {
            $tag       = new BatchLoadTag();
            $tag->name = $name;
            $shared[]  = $tag;
            $this->_em->persist($tag);
        }

        for ($i = 0; $i < self::OWNER_COUNT; $i++) {
            $owner       = new BatchLoadOwner();
            $owner->name = 'owner ' . $i;

            $profile        = new BatchLoadProfile();
            $profile->bio   = 'bio ' . $i;
            $profile->owner = $owner;
            $owner->profile = $profile;

            foreach (['a', 'b'] as $suffix) {
                $child        = new BatchLoadChild();
                $child->code  = $i . $suffix;
                $child->owner = $owner;
                $owner->children->add($child);

                $indexed               = new BatchLoadChild();
                $indexed->code         = 'i' . $i . $suffix;
                $indexed->indexedOwner = $owner;
                $owner->indexedChildren->set($indexed->code, $indexed);

                $this->_em->persist($child);
                $this->_em->persist($indexed);
            }

            $own       = new BatchLoadTag();
            $own->name = 'own ' . $i;
            $this->_em->persist($own);

            foreach ([...$shared, $own] as $tag) {
                $owner->tags->add($tag);
            }

            $this->_em->persist($owner);
            $this->_em->persist($profile);

            $this->_em->flush();
        }

        $this->_em->clear();
    }

    /** @return list<BatchLoadOwner> */
    protected function owners(): array
    {
        return $this->_em->createQuery('SELECT o FROM ' . BatchLoadOwner::class . ' o ORDER BY o.id')->getResult();
    }

    /** The number an owner was created with, so assertions do not depend on result order. */
    protected function indexOf(BatchLoadOwner $owner): string
    {
        return substr($owner->name, strlen('owner '));
    }
}

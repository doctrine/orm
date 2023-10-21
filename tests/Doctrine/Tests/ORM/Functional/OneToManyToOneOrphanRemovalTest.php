<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Tests\Models\Rating\Business;
use Doctrine\Tests\Models\Rating\Review;
use Doctrine\Tests\Models\Rating\Reviews;
use Doctrine\Tests\Models\Rating\User;
use Doctrine\Tests\OrmFunctionalTestCase;

use function assert;
use function count;
use function is_int;

/**
 * Tests a bidirectional many-to-many with joint table (effectively a one to many) association mapping with orphan
 * removal.
 */
class OneToManyToOneOrphanRemovalTest extends OrmFunctionalTestCase
{
    private string $businessId;
    private Collection $replaceReviews;
    private User $user2;

    protected function setUp(): void
    {
        $this->useModelSet('rating');

        parent::setUp();

        $this->setUpBusiness();
    }

    private function setUpBusiness(): void
    {
        $user1       = new User('user1', 'User 1');
        $user2       = new User('user2', 'User 2');
        $this->user2 = $user2;
        $user3       = new User('user3', 'User 3');
        $user4       = new User('user4', 'User 4');

        $review1 = new Review('review1', 'review 1', 4, $user1);
        $review2 = new Review('review2', 'review 2', 5, $user2);
        $review3 = new Review('review3', 'review 3', 2, $user3);
        $review4 = new Review('review4', 'review 4', 1, $user4);

        $reviews = new Reviews();
        $reviews->add($review1);
        $reviews->add($review2);
        $reviews->add($review3);
        $reviews->add($review4);

        $replaceReviews                            = new Reviews();
        $modifiedReviewFromOuterApplicationContext = new Review('review3', 'review 3 updated', 2, $user3);
        $newReview5                                = new Review('review5', 'review 5', 2, $user3);
        $replaceReviews->add($review1);
        $replaceReviews->add($modifiedReviewFromOuterApplicationContext);
        $replaceReviews->add($review4);
        $replaceReviews->add($newReview5);
        $this->replaceReviews = $replaceReviews;

        $business = new Business('business1', 'business One', $reviews);

        $this->_em->persist($business);
        $this->_em->flush();

        $this->businessId = $business->getId();

        $this->_em->clear();
    }

    public function testOrphanRemoval(): void
    {
        $businessProxy = $this->_em->getReference(Business::class, $this->businessId);

        $this->_em->remove($businessProxy);
        $this->_em->flush();
        $this->_em->clear();

        $query  = $this->_em->createQuery('SELECT b FROM Doctrine\Tests\Models\Rating\Business b');
        $result = $query->getResult();

        self::assertCount(0, $result, 'Business should be removed by EntityManager');

        $query  = $this->_em->createQuery('SELECT r FROM Doctrine\Tests\Models\Rating\Review r');
        $result = $query->getResult();

        self::assertCount(0, $result, 'Review should be removed by orphanRemoval');

        $query  = $this->_em->createQuery('SELECT u FROM Doctrine\Tests\Models\Rating\User u');
        $result = $query->getResult();

        self::assertCount(0, $result, 'User should be removed by orphanRemoval');
    }

    public function testOrphanRemovalRemoveFromCollection(): void
    {
        $business = $this->_em->find(Business::class, $this->businessId);

        $reviews = $business->getReviews();
        $reviews->remove(1);
        $reviews->removeElement($reviews->last());
        $business->setReviews($reviews);

        $this->_em->flush();
        $this->_em->clear();

        $query  = $this->_em->createQuery('SELECT r FROM Doctrine\Tests\Models\Rating\Review r');
        $result = $query->getResult();

        self::assertCount(2, $result, 'Review should be removed by orphanRemoval');

        $query  = $this->_em->createQuery('SELECT u FROM Doctrine\Tests\Models\Rating\User u');
        $result = $query->getResult();

        self::assertCount(2, $result, 'User should be removed by orphanRemoval');
    }

    public function testOrphanRemovalRemoveUpdateWithNewCollection(): void
    {
        $business          = $this->_em->find(Business::class, $this->businessId);
        $reconciledReviews = $this->reconcileReviewsCollection($this->replaceReviews, $business->getReviews());
        //$business->setReviews($reconciledReviews);

        $this->_em->flush();
        $this->_em->clear();

        $query  = $this->_em->createQuery('SELECT r FROM Doctrine\Tests\Models\Rating\Review r');
        $result = $query->getResult();

        self::assertCount(4, $result, 'Review should be updated correctly');

        $query  = $this->_em->createQuery('SELECT u FROM Doctrine\Tests\Models\Rating\User u');
        $result = $query->getResult();
        self::assertCount(3, $result, 'Should have 3 users instead of result');

        self::assertNull($this->_em->find(Review::class, 'review2'));

        $business         = $this->_em->find(Business::class, $this->businessId);
        $newReviews       = $business->getReviews();
        $lastOfNewReviews = $this->arrayCollectionContainsByElementProperty($newReviews, 'id', $this->replaceReviews->last()->getId());
        self::assertEquals(
            $this->replaceReviews->last()->getId(),
            $lastOfNewReviews->getId()
        );
    }

    public function testOrphanRemovalRemoveUpdateDeletingReviewWithUserAndAddingReviewWithSameUser(): void
    {
        $business   = $this->_em->find(Business::class, $this->businessId);
        $newReview6 = new Review('review6', 'Just another review 6', 4, $this->user2);
        $this->replaceReviews->add($newReview6);

        $reconciledReviews = $this->reconcileReviewsCollection($this->replaceReviews, $business->getReviews());

        $this->_em->flush();
        $this->_em->clear();

        $query  = $this->_em->createQuery('SELECT r FROM Doctrine\Tests\Models\Rating\Review r');
        $result = $query->getResult();

        self::assertCount(5, $result, 'Review should be updated correctly');

        $query  = $this->_em->createQuery('SELECT u FROM Doctrine\Tests\Models\Rating\User u');
        $result = $query->getResult();
        self::assertCount(4, $result, 'Should have 3 users instead of result');

        self::assertNull($this->_em->find(Review::class, 'review2'));
        self::assertNotNull($this->_em->find(User::class, 'user2'));

        $business         = $this->_em->find(Business::class, $this->businessId);
        $newReviews       = $business->getReviews();
        $lastOfNewReviews = $this->arrayCollectionContainsByElementProperty($newReviews, 'id', $this->replaceReviews->last()->getId());
        self::assertEquals(
            $newReview6->getId(),
            $lastOfNewReviews->getId()
        );
    }

    private function reconcileReviewsCollection(Collection $newCollection, Collection $persistedCollection): Collection
    {
        foreach ($persistedCollection as $itemKey => $existingCollectionItem) {
            assert(is_int($itemKey));
            assert($existingCollectionItem instanceof Review);
            $updatedCollectionItem = $this->arrayCollectionContainsByElementProperty(
                $newCollection,
                'id',
                $existingCollectionItem->getId()
            );
            assert($updatedCollectionItem instanceof Review);

            // if element still exists, update it, else remove it
            if ($updatedCollectionItem) {
                echo "\nUpdating id: " . $updatedCollectionItem->getId();
                $newCollection->removeElement($updatedCollectionItem);
                $this->updateReview($existingCollectionItem->getId(), $updatedCollectionItem);
            } else {
                echo "\nRemoving id: " . $existingCollectionItem->getId();
                $persistedCollection->removeElement($existingCollectionItem);
            }
        }

        if (! $newCollection->isEmpty()) {
            echo "\nNew Collection not empty: " . $newCollection->count();
            foreach ($newCollection as $updatedCollectionItem) {
                echo "\nAdding id: " . $updatedCollectionItem->getId();

                $userId       = $updatedCollectionItem->getUser()->getId();
                $existingUser = $this->entityInstanceExists(User::class, 'id', $userId);
                if ($existingUser) {
                    echo "\nDetecting existing user and adding to review: " . $existingUser->getId();
                    $updatedCollectionItem->setUser($existingUser);
                }

                $persistedCollection->add($updatedCollectionItem);
            }
        }

        return $persistedCollection;
    }

    private function updateReview(string $reviewId, object $newReview): void
    {
        $reviewFound = $this->_em->find(Review::class, $reviewId);

        if ($reviewFound) {
            $reviewFound->setRating($newReview->getRating());
            $reviewFound->setText($newReview->getText());
            $reviewFound->setRating($newReview->getRating());
            //$existingUser = $reviewFound->getUser();
            //$existingUser->setName($updatedCollectionItem->getUser()->getName());
        }
    }

    private function entityInstanceExists(string $entityName, string $idName, $idValue): object|bool
    {
        $query = $this->_em->createQuery(
            'SELECT e FROM ' . $entityName . ' e WHERE e.' . $idName . ' = :id'
        );
        $query->setParameter('id', $idValue);
        $entityFound = $query->getResult();
        if (count($entityFound) > 0) {
            return $entityFound[0];
        }

        return false;
    }

    private function arrayCollectionContainsByElementProperty(Selectable $collection, string $elProperty, string $elVal): bool|object
    {
        $exp      = new Comparison($elProperty, '=', $elVal);
        $criteria = new Criteria();
        $criteria->where($exp);

        $matchedCollection = $collection->matching($criteria);
        if (! $matchedCollection->isEmpty()) {
            return $matchedCollection->first();
        }

        return false;
    }

//    public function testOrphanRemovalRemoveWithExternallyBuiltArrayCollection(): void
//    {
//        $business = $this->_em->find(Business::class, $this->businessId);
//
//        $newReviews    = new Reviews();
//        $newReviewUser = new User('newUser', 'New User');
//        $newReviews->add(new Review('newReview1', 'new review 1', 4, $newReviewUser));
//
//        $oldReviews = $business->getReviews();
//
////        $elm = $oldReviews->first()
//        var_dump( $this->arrayCollectionContainsByElementProperty($oldReviews, 'id', 'review177')); exit;
//
//        $business->setReviews($newReviews);
//
//        $this->_em->flush();
//        $this->_em->clear();
//
//        $query  = $this->_em->createQuery('SELECT r FROM Doctrine\Tests\Models\Rating\Review r');
//        $result = $query->getResult();
//
//        self::assertCount(1, $result, 'Old Reviews should be removed by orphanRemoval');
//
//        $query  = $this->_em->createQuery('SELECT u FROM Doctrine\Tests\Models\Rating\User u');
//        $result = $query->getResult();
//
//        self::assertCount(1, $result, 'User should be removed by orphanRemoval');
//    }

//    /** @group DDC-3382 */
//    public function testOrphanRemovalClearCollectionAndReAdd(): void
//    {
//        $user = $this->_em->find(CmsUser::class, $this->userId);
//
//        $phone1 = $user->getPhonenumbers()->first();
//
//        $user->getPhonenumbers()->clear();
//        $user->addPhonenumber($phone1);
//
//        $this->_em->flush();
//
//        $query  = $this->_em->createQuery('SELECT p FROM Doctrine\Tests\Models\CMS\CmsPhonenumber p');
//        $result = $query->getResult();
//
//        self::assertCount(1, $result, 'CmsPhonenumber should be removed by orphanRemoval');
//    }
//
//    /** @group DDC-2524 */
//    public function testOrphanRemovalClearCollectionAndAddNew(): void
//    {
//        $user     = $this->_em->find(CmsUser::class, $this->userId);
//        $newPhone = new CmsPhonenumber();
//
//        $newPhone->phonenumber = '654321';
//
//        $user->getPhonenumbers()->clear();
//        $user->addPhonenumber($newPhone);
//
//        $this->_em->flush();
//
//        $query  = $this->_em->createQuery('SELECT p FROM Doctrine\Tests\Models\CMS\CmsPhonenumber p');
//        $result = $query->getResult();
//
//        self::assertCount(1, $result, 'Old CmsPhonenumbers should be removed by orphanRemoval and new one added');
//    }
//
//    /** @group DDC-1496 */
//    public function testOrphanRemovalUnitializedCollection(): void
//    {
//        $user = $this->_em->find(CmsUser::class, $this->userId);
//
//        $user->phonenumbers->clear();
//        $this->_em->flush();
//
//        $query  = $this->_em->createQuery('SELECT p FROM Doctrine\Tests\Models\CMS\CmsPhonenumber p');
//        $result = $query->getResult();
//
//        self::assertCount(0, $result, 'CmsPhonenumber should be removed by orphanRemoval');
//    }
}

<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;

class DDC735Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(DDC735Product::class),
                $this->em->getClassMetadata(DDC735Review::class)
                ]
            );
        } catch(\Exception $e) {

        }
    }

    public function testRemoveElement_AppliesOrphanRemoval()
    {
        // Create a product and its first review
        $product = new DDC735Product;
        $review  = new DDC735Review($product);

        // Persist and flush
        $this->em->persist($product);
        $this->em->flush();

        // Now you see it
        self::assertEquals(1, count($product->getReviews()));

        // Remove the review
        $reviewId = $review->getId();
        $product->removeReview($review);
        $this->em->flush();

        // Now you don't
        self::assertEquals(0, count($product->getReviews()), 'count($reviews) should be 0 after removing its only Review');

        // Refresh
        $this->em->refresh($product);

        // It should still be 0
        self::assertEquals(0, count($product->getReviews()), 'count($reviews) should still be 0 after the refresh');

        // Review should also not be available anymore
        self::assertNull($this->em->find(DDC735Review::class, $reviewId));
    }
}

/**
 * @Entity
 */
class DDC735Product
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     */
    protected $id;

    /**
     * @OneToMany(
     *   targetEntity="DDC735Review",
     *   mappedBy="product",
     *   cascade={"persist"},
     *   orphanRemoval=true
     * )
     */
    protected $reviews;

    public function __construct()
    {
        $this->reviews = new ArrayCollection;
    }

    public function getReviews()
    {
        return $this->reviews;
    }

    public function addReview(DDC735Review $review)
    {
        $this->reviews->add($review);
    }

    public function removeReview(DDC735Review $review)
    {
        $this->reviews->removeElement($review);
    }
}

/**
 * @Entity
 */
class DDC735Review
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="DDC735Product", inversedBy="reviews")
     */
    protected $product;

    public function __construct(DDC735Product $product)
    {
        $this->product = $product;
        $product->addReview($this);
    }

    public function getId()
    {
        return $this->id;
    }
}

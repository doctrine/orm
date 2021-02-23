<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;

class DDC735Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        try {
            $this->schemaTool->createSchema(
                [
                    $this->em->getClassMetadata(DDC735Product::class),
                    $this->em->getClassMetadata(DDC735Review::class),
                ]
            );
        } catch (Exception $e) {
        }
    }

    public function testRemoveElementAppliesOrphanRemoval() : void
    {
        // Create a product and its first review
        $product = new DDC735Product();
        $review  = new DDC735Review($product);

        // Persist and flush
        $this->em->persist($product);
        $this->em->flush();

        // Now you see it
        self::assertCount(1, $product->getReviews());

        // Remove the review
        $reviewId = $review->getId();
        $product->removeReview($review);
        $this->em->flush();

        // Now you don't
        self::assertCount(0, $product->getReviews(), 'count($reviews) should be 0 after removing its only Review');

        // Refresh
        $this->em->refresh($product);

        // It should still be 0
        self::assertCount(0, $product->getReviews(), 'count($reviews) should still be 0 after the refresh');

        // Review should also not be available anymore
        self::assertNull($this->em->find(DDC735Review::class, $reviewId));
    }
}

/**
 * @ORM\Entity
 */
class DDC735Product
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    protected $id;

    /**
     * @ORM\OneToMany(
     *   targetEntity=DDC735Review::class,
     *   mappedBy="product",
     *   cascade={"persist"},
     *   orphanRemoval=true
     * )
     */
    protected $reviews;

    public function __construct()
    {
        $this->reviews = new ArrayCollection();
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
 * @ORM\Entity
 */
class DDC735Review
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    protected $id;

    /** @ORM\ManyToOne(targetEntity=DDC735Product::class, inversedBy="reviews") */
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

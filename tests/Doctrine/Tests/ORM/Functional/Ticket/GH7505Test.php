<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

final class GH7505Test extends OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH7505AbstractResponse::class,
            GH7505ArrayResponse::class,
            GH7505TextResponse::class,
        ]);
    }

    public function testSimpleArrayTypeHydratedCorrectly() : void
    {
        $arrayResponse = new GH7505ArrayResponse();
        $this->em->persist($arrayResponse);

        $textResponse = new GH7505TextResponse();
        $this->em->persist($textResponse);

        $this->em->flush();
        $this->em->clear();

        $repository = $this->em->getRepository(GH7505AbstractResponse::class);

        /** @var GH7505ArrayResponse $arrayResponse */
        $arrayResponse = $repository->find($arrayResponse->id);
        self::assertSame([], $arrayResponse->value);

        /** @var GH7505TextResponse $textResponse */
        $textResponse = $repository->find($textResponse->id);
        self::assertNull($textResponse->value);
    }
}

/**
 * @ORM\Entity()
 * @ORM\Table(name="gh7505_responses")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({
 *     "array" = GH7505ArrayResponse::class,
 *     "text"  = GH7505TextResponse::class,
 * })
 */
abstract class GH7505AbstractResponse
{
    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public $id;
}

/**
 * @ORM\Entity()
 */
class GH7505ArrayResponse extends GH7505AbstractResponse
{
    /**
     * @ORM\Column(name="value_array", type="simple_array")
     *
     * @var array
     */
    public $value = [];
}

/**
 * @ORM\Entity()
 */
class GH7505TextResponse extends GH7505AbstractResponse
{
    /**
     * @ORM\Column(name="value_string", type="string")
     *
     * @var string|null
     */
    public $value;
}

<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;

class DDC588Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(DDC588Site::class);
    }

    #[DoesNotPerformAssertions]
    public function testIssue(): void
    {
        $site = new DDC588Site('Foo');

        $this->_em->persist($site);
        $this->_em->flush();
        // Following should not result in exception
        $this->_em->refresh($site);
    }
}

#[Entity]
class DDC588Site
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer', name: 'site_id')]
    #[GeneratedValue]
    public $id;

    public function __construct(
        #[Column(type: 'string', length: 45)]
        protected string $name = '',
    ) {
    }
}

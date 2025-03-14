<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\SkipPrivateSetProperties;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'Test_Class_With_Private_Set_Properties_Order')]
class OrderPrivateSetProperties
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public int $id;

    #[ORM\Column]
    private(set) string $company;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: UserPrivateSetProperties::class, fetch: 'LAZY')]
        private(set) UserPrivateSetProperties $user,
    ) {
        $this->company = $user->company;
    }
}

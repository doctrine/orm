<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11149;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table('gh11149_locale')]
class Locale
{
    #[ORM\Id]
    #[ORM\Column(length: 5)]
    public string $code;

    public function __construct(string $code)
    {
        $this->code = $code;
    }
}

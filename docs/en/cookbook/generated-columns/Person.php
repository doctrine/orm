<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Person
{
    #[ORM\Column(type: 'string')]
    private string $firstName;

    #[ORM\Column(type: 'string', name: 'name')]
    private string $lastName;

    #[ORM\Column(
        type: 'string',
        insertable: false,
        updatable: false,
        columnDefinition: "VARCHAR(255) GENERATED ALWAYS AS (concat(firstName, ' ', name) stored NOT NULL",
        generated: 'ALWAYS',
    )]
    private string $fullName;
}

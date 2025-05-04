<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    /**
     * When working with Postgres, it is recommended to use the jsonb
     * format for better performance.
     */
    #[ORM\Column(options: ['jsonb' => true])]
    private array $content;

    /**
     * Because we specify NOT NULL, inserting will fail if the content does
     * not have a string in the title field.
     */
    #[ORM\Column(
        insertable: false,
        updatable: false,
        columnDefinition: "VARCHAR(255) generated always as (content->>'title') stored NOT NULL",
        generated: 'ALWAYS',
    )]
    private string $title;
}

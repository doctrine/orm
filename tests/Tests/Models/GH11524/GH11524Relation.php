<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH11524;

use Doctrine\ORM\Mapping as ORM;
use LogicException;

#[ORM\Entity]
#[ORM\Table(name: 'gh11524_relations')]
class GH11524Relation
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public int|null $id;

    #[ORM\Column(type: 'string')]
    public string $name;

    private string|null $currentLocale;

    public function setCurrentLocale(string $locale): void
    {
        $this->currentLocale = $locale;
    }

    public function getTranslation(): string
    {
        if ($this->currentLocale === null) {
            throw new LogicException('The current locale must be set to retrieve translation.');
        }

        return 'fake';
    }
}

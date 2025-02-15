<?php
// phpcs:ignoreFile
namespace Doctrine\Tests\Models\PropertyHooks;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'property_hooks_user')]
class User
{
    #[Id, GeneratedValue, Column(type: Types::INTEGER)]
    public ?int $id;

    #[Column(type: Types::STRING)]
    public string $first {
        set {
            if (strlen($value) === 0) {
                throw new ValueError("Name must be non-empty");
            }
            $this->first = $value;
        }
    }

    #[Column(type: Types::STRING)]
    public string $last {
        set {
            if (strlen($value) === 0) {
                throw new ValueError("Name must be non-empty");
            }
            $this->last = $value;
        }
    }

    public string $fullName {
        // Override the "read" action with arbitrary logic.
        get => $this->first . " " . $this->last;

        // Override the "write" action with arbitrary logic.
        set {
            [$this->first, $this->last] = explode(' ', $value, 2);
        }
    }

    #[Column(type: Types::STRING)]
    public string $language = 'de' {
        // Override the "read" action with arbitrary logic.
        get => strtoupper($this->language);

        // Override the "write" action with arbitrary logic.
        set {
            $this->language = strtolower($value);
        }
    }
}

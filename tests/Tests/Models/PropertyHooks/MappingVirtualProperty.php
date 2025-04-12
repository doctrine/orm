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
class MappingVirtualProperty
{
    #[Id, GeneratedValue, Column(type: Types::INTEGER)]
    public ?int $id;

    #[Column(type: Types::STRING)]
    public string $first;

    #[Column(type: Types::STRING)]
    public string $last;

    #[Column(type: Types::STRING)]
    public string $fullName {
        get => $this->first . " " . $this->last;
        set {
            [$this->first, $this->last] = explode(' ', $value, 2);
        }
    }
}

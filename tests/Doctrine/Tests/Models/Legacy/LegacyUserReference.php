<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Legacy;

use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'legacy_users_reference')]
#[Entity]
class LegacyUserReference
{
    #[Id]
    #[ManyToOne(targetEntity: 'LegacyUser', inversedBy: 'references')]
    #[JoinColumn(name: 'iUserIdSource', referencedColumnName: 'iUserId')]
    private LegacyUser $_source;

    #[Id]
    #[ManyToOne(targetEntity: 'LegacyUser')]
    #[JoinColumn(name: 'iUserIdTarget', referencedColumnName: 'iUserId')]
    private LegacyUser $_target;

    #[Column(type: 'datetime', name: 'created')]
    private DateTime $created;

    public function __construct(
        LegacyUser $source,
        LegacyUser $target,
        #[Column(type: 'string', length: 255, name: 'description')]
        private string $_description,
    ) {
        $source->addReference($this);
        $target->addReference($this);

        $this->_source = $source;
        $this->_target = $target;
        $this->created = new DateTime('now');
    }

    public function source(): LegacyUser
    {
        return $this->_source;
    }

    public function target(): LegacyUser
    {
        return $this->_target;
    }

    public function setDescription(string $desc): void
    {
        $this->_description = $desc;
    }

    public function getDescription(): string
    {
        return $this->_description;
    }
}

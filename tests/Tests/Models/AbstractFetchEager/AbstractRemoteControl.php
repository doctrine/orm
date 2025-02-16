<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\AbstractFetchEager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'abstract_fetch_eager_remote_control')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap(['mobile' => 'MobileRemoteControl'])]
abstract class AbstractRemoteControl
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\Column(type: 'string')]
    public string $name;

    /** @var Collection<User> */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'remoteControl', fetch: 'EAGER')]
    public Collection $users;

    public function __construct(string $name)
    {
        $this->name  = $name;
        $this->users = new ArrayCollection();
    }
}

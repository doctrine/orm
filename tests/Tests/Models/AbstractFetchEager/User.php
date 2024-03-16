<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\AbstractFetchEager;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'abstract_fetch_eager_user')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\ManyToOne(targetEntity: AbstractRemoteControl::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    public AbstractRemoteControl $remoteControl;

    public function __construct(AbstractRemoteControl $control)
    {
        $this->remoteControl = $control;
    }
}

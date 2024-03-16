<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\AbstractFetchEager;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="abstract_fetch_eager_user")
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="AbstractRemoteControl", inversedBy="users")
     * @ORM\JoinColumn(nullable=false)
     *
     * @var AbstractRemoteControl
     */
    public $remoteControl;

    public function __construct(AbstractRemoteControl $control)
    {
        $this->remoteControl = $control;
    }
}

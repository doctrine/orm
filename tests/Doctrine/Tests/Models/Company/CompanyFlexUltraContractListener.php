<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Event\LifecycleEventArgs;
use function func_get_args;

class CompanyFlexUltraContractListener
{
    public $prePersistCalls;

    /**
     * @ORM\PrePersist
     */
    public function prePersistHandler1(CompanyContract $contract, LifecycleEventArgs $args)
    {
        $this->prePersistCalls[] = func_get_args();
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersistHandler2(CompanyContract $contract, LifecycleEventArgs $args)
    {
        $this->prePersistCalls[] = func_get_args();
    }
}

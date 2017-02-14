<?php

namespace Doctrine\Tests\Models\Company;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Event\LifecycleEventArgs;

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

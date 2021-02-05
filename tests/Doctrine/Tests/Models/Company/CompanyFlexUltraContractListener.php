<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

use Doctrine\ORM\Event\LifecycleEventArgs;

use function func_get_args;

class CompanyFlexUltraContractListener
{
    public $prePersistCalls;

    /**
     * @PrePersist
     */
    public function prePersistHandler1(CompanyContract $contract, LifecycleEventArgs $args): void
    {
        $this->prePersistCalls[] = func_get_args();
    }

    /**
     * @PrePersist
     */
    public function prePersistHandler2(CompanyContract $contract, LifecycleEventArgs $args): void
    {
        $this->prePersistCalls[] = func_get_args();
    }
}

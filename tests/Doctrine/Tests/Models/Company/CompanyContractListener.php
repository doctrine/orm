<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

use function func_get_args;

class CompanyContractListener
{
    public $postPersistCalls;
    public $prePersistCalls;

    public $postUpdateCalls;
    public $preUpdateCalls;

    public $postRemoveCalls;
    public $preRemoveCalls;

    public $preFlushCalls;

    public $postLoadCalls;

    /**
     * @PostPersist
     */
    public function postPersistHandler(CompanyContract $contract): void
    {
        $this->postPersistCalls[] = func_get_args();
    }

    /**
     * @PrePersist
     */
    public function prePersistHandler(CompanyContract $contract): void
    {
        $this->prePersistCalls[] = func_get_args();
    }

    /**
     * @PostUpdate
     */
    public function postUpdateHandler(CompanyContract $contract): void
    {
        $this->postUpdateCalls[] = func_get_args();
    }

    /**
     * @PreUpdate
     */
    public function preUpdateHandler(CompanyContract $contract): void
    {
        $this->preUpdateCalls[] = func_get_args();
    }

    /**
     * @PostRemove
     */
    public function postRemoveHandler(CompanyContract $contract): void
    {
        $this->postRemoveCalls[] = func_get_args();
    }

    /**
     * @PreRemove
     */
    public function preRemoveHandler(CompanyContract $contract): void
    {
        $this->preRemoveCalls[] = func_get_args();
    }

    /**
     * @PreFlush
     */
    public function preFlushHandler(CompanyContract $contract): void
    {
        $this->preFlushCalls[] = func_get_args();
    }

    /**
     * @PostLoad
     */
    public function postLoadHandler(CompanyContract $contract): void
    {
        $this->postLoadCalls[] = func_get_args();
    }
}

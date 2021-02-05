<?php

namespace Doctrine\Tests\Models\Company;

use Doctrine\ORM\Mapping as ORM;

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
    #[ORM\PostPersist]
    public function postPersistHandler(CompanyContract $contract): void
    {
        $this->postPersistCalls[] = func_get_args();
    }

    /**
     * @PrePersist
     */
    #[ORM\PrePersist]
    public function prePersistHandler(CompanyContract $contract): void
    {
        $this->prePersistCalls[] = func_get_args();
    }

    /**
     * @PostUpdate
     */
    #[ORM\PostUpdate]
    public function postUpdateHandler(CompanyContract $contract): void
    {
        $this->postUpdateCalls[] = func_get_args();
    }

    /**
     * @PreUpdate
     */
    #[ORM\PreUpdate]
    public function preUpdateHandler(CompanyContract $contract): void
    {
        $this->preUpdateCalls[] = func_get_args();
    }

    /**
     * @PostRemove
     */
    #[ORM\PostRemove]
    public function postRemoveHandler(CompanyContract $contract): void
    {
        $this->postRemoveCalls[] = func_get_args();
    }

    /**
     * @PreRemove
     */
    #[ORM\PreRemove]
    public function preRemoveHandler(CompanyContract $contract): void
    {
        $this->preRemoveCalls[] = func_get_args();
    }

    /**
     * @PreFlush
     */
    #[ORM\PreFlush]
    public function preFlushHandler(CompanyContract $contract): void
    {
        $this->preFlushCalls[] = func_get_args();
    }

    /**
     * @PostLoad
     */
    #[ORM\PostLoad]
    public function postLoadHandler(CompanyContract $contract): void
    {
        $this->postLoadCalls[] = func_get_args();
    }
}

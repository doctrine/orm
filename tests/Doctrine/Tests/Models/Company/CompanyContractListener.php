<?php

namespace Doctrine\Tests\Models\Company;

use Doctrine\ORM\Annotation as ORM;

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
     * @ORM\PostPersist
     */
    public function postPersistHandler(CompanyContract $contract)
    {
        $this->postPersistCalls[] = func_get_args();
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersistHandler(CompanyContract $contract)
    {
        $this->prePersistCalls[] = func_get_args();
    }

    /**
     * @ORM\PostUpdate
     */
    public function postUpdateHandler(CompanyContract $contract)
    {
        $this->postUpdateCalls[] = func_get_args();
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdateHandler(CompanyContract $contract)
    {
        $this->preUpdateCalls[] = func_get_args();
    }

    /**
     * @ORM\PostRemove
     */
    public function postRemoveHandler(CompanyContract $contract)
    {
        $this->postRemoveCalls[] = func_get_args();
    }

    /**
     * @ORM\PreRemove
     */
    public function preRemoveHandler(CompanyContract $contract)
    {
        $this->preRemoveCalls[] = func_get_args();
    }

    /**
     * @ORM\PreFlush
     */
    public function preFlushHandler(CompanyContract $contract)
    {
        $this->preFlushCalls[] = func_get_args();
    }

    /**
     * @ORM\PostLoad
     */
    public function postLoadHandler(CompanyContract $contract)
    {
        $this->postLoadCalls[] = func_get_args();
    }
}

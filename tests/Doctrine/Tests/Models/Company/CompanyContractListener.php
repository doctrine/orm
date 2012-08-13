<?php

namespace Doctrine\Tests\Models\Company;

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
    public function postPersistHandler(CompanyContract $contract)
    {
        $this->postPersistCalls[] = func_get_args();
    }

    /**
     * @PrePersist
     */
    public function prePersistHandler(CompanyContract $contract)
    {
        $this->prePersistCalls[] = func_get_args();
    }

    /**
     * @PostUpdate
     */
    public function postUpdateHandler(CompanyContract $contract)
    {
        $this->postUpdateCalls[] = func_get_args();
    }

    /**
     * @PreUpdate
     */
    public function preUpdateHandler(CompanyContract $contract)
    {
        $this->preUpdateCalls[] = func_get_args();
    }

    /**
     * @PostRemove
     */
    public function postRemoveHandler(CompanyContract $contract)
    {
        $this->postRemoveCalls[] = func_get_args();
    }

    /**
     * @PreRemove
     */
    public function preRemoveHandler(CompanyContract $contract)
    {
        $this->preRemoveCalls[] = func_get_args();
    }

    /**
     * @PreFlush
     */
    public function preFlushHandler(CompanyContract $contract)
    {
        $this->preFlushCalls[] = func_get_args();
    }

    /**
     * @PostLoad
     */
    public function postLoadHandler(CompanyContract $contract)
    {
        $this->postLoadCalls[] = func_get_args();
    }

}

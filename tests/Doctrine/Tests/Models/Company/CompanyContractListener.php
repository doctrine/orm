<?php

namespace Doctrine\Tests\Models\Company;

class CompanyContractListener
{
    const PRE_PERSIST = 0;

    public $postPersistCalls;
    public $prePersistCalls;

    public $postUpdateCalls;
    public $preUpdateCalls;

    public $postRemoveCalls;
    public $preRemoveCalls;

    public $preFlushCalls;

    public $postLoadCalls;

    public $snapshots = [];

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
        $this->snapshots[self::PRE_PERSIST][] = $this->takeSnapshot($contract);
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

    public function takeSnapshot(CompanyContract $contract)
    {
        $snapshot = [];
        $reflexion = new \ReflectionClass($contract);
        foreach ($reflexion->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($contract);
            if (is_object($value) || is_array($value)) {
                continue;
            }
            $snapshot[$property->getName()] = $property->getValue($contract);
        }

        return $snapshot;
    }

}

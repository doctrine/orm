<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

use Doctrine\ORM\Mapping as ORM;

use function func_get_args;

class CompanyContractListener
{
    /** @phpstan-var list<list<mixed>> */
    public $postPersistCalls;

    /** @phpstan-var list<list<mixed>> */
    public $prePersistCalls;

    /** @phpstan-var list<list<mixed>> */
    public $postUpdateCalls;

    /** @phpstan-var list<list<mixed>> */
    public $preUpdateCalls;

    /** @phpstan-var list<list<mixed>> */
    public $postRemoveCalls;

    /** @phpstan-var list<list<mixed>> */
    public $preRemoveCalls;

    /** @phpstan-var list<list<mixed>> */
    public $preFlushCalls;

    /** @phpstan-var list<list<mixed>> */
    public $postLoadCalls;

    #[ORM\PostPersist]
    public function postPersistHandler(CompanyContract $contract): void
    {
        $this->postPersistCalls[] = func_get_args();
    }

    #[ORM\PrePersist]
    public function prePersistHandler(CompanyContract $contract): void
    {
        $this->prePersistCalls[] = func_get_args();
    }

    #[ORM\PostUpdate]
    public function postUpdateHandler(CompanyContract $contract): void
    {
        $this->postUpdateCalls[] = func_get_args();
    }

    #[ORM\PreUpdate]
    public function preUpdateHandler(CompanyContract $contract): void
    {
        $this->preUpdateCalls[] = func_get_args();
    }

    #[ORM\PostRemove]
    public function postRemoveHandler(CompanyContract $contract): void
    {
        $this->postRemoveCalls[] = func_get_args();
    }

    #[ORM\PreRemove]
    public function preRemoveHandler(CompanyContract $contract): void
    {
        $this->preRemoveCalls[] = func_get_args();
    }

    #[ORM\PreFlush]
    public function preFlushHandler(CompanyContract $contract): void
    {
        $this->preFlushCalls[] = func_get_args();
    }

    #[ORM\PostLoad]
    public function postLoadHandler(CompanyContract $contract): void
    {
        $this->postLoadCalls[] = func_get_args();
    }
}

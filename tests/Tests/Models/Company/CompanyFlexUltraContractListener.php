<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\PrePersist;

use function func_get_args;

class CompanyFlexUltraContractListener
{
    /** @psalm-var list<mixed[]> */
    public $prePersistCalls;

    /** @PrePersist */
    #[ORM\PrePersist]
    public function prePersistHandler1(CompanyContract $contract, PrePersistEventArgs $args): void
    {
        $this->prePersistCalls[] = func_get_args();
    }

    /** @PrePersist */
    #[ORM\PrePersist]
    public function prePersistHandler2(CompanyContract $contract, PrePersistEventArgs $args): void
    {
        $this->prePersistCalls[] = func_get_args();
    }
}

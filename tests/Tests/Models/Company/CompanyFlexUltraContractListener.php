<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Mapping as ORM;

use function func_get_args;

class CompanyFlexUltraContractListener
{
    /** @phpstan-var list<mixed[]> */
    public $prePersistCalls;

    #[ORM\PrePersist]
    public function prePersistHandler1(CompanyContract $contract, PrePersistEventArgs $args): void
    {
        $this->prePersistCalls[] = func_get_args();
    }

    #[ORM\PrePersist]
    public function prePersistHandler2(CompanyContract $contract, PrePersistEventArgs $args): void
    {
        $this->prePersistCalls[] = func_get_args();
    }
}

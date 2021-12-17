<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CMS;

use BadMethodCallException;

use function func_get_args;

class CmsAddressListener
{
    /** @psalm-var array<string, list<list<mixed>>> */
    public $calls;

    public function prePersist(): void
    {
        $this->calls[__FUNCTION__][] = func_get_args();
    }

    public function postPersist(): void
    {
        $this->calls[__FUNCTION__][] = func_get_args();
    }

    public function preUpdate(): void
    {
        $this->calls[__FUNCTION__][] = func_get_args();
    }

    public function postUpdate(): void
    {
        $this->calls[__FUNCTION__][] = func_get_args();
    }

    public function preRemove(): void
    {
        $this->calls[__FUNCTION__][] = func_get_args();
    }

    public function postRemove(): void
    {
        $this->calls[__FUNCTION__][] = func_get_args();
    }

    public function postLoad(): void
    {
        $this->calls[__FUNCTION__][] = func_get_args();
    }

    public function preFlush(): void
    {
        $this->calls[__FUNCTION__][] = func_get_args();
    }

    protected function postPersistHandler(): void
    {
        throw new BadMethodCallException('This is not a valid callback');
    }

    protected function prePersistHandler(): void
    {
        throw new BadMethodCallException('This is not a valid callback');
    }
}

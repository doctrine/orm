<?php

namespace Doctrine\Tests\Models\CMS;

class CmsAddressListener
{
    public $calls;

    public function prePersist()
    {
        $this->calls[__FUNCTION__][] = func_get_args();
    }

    public function postPersist()
    {
        $this->calls[__FUNCTION__][] = func_get_args();
    }
    
    public function preUpdate()
    {
        $this->calls[__FUNCTION__][] = func_get_args();
    }

    public function postUpdate()
    {
        $this->calls[__FUNCTION__][] = func_get_args();
    }

    public function preRemove()
    {
        $this->calls[__FUNCTION__][] = func_get_args();
    }

    public function postRemove()
    {
        $this->calls[__FUNCTION__][] = func_get_args();
    }

    public function postLoad()
    {
        $this->calls[__FUNCTION__][] = func_get_args();
    }

    public function preFlush()
    {
        $this->calls[__FUNCTION__][] = func_get_args();
    }

    protected function postPersistHandler()
    {
        throw new \BadMethodCallException("This is not a valid callback");
    }

    protected function prePersistHandler()
    {
        throw new \BadMethodCallException("This is not a valid callback");
    }
}
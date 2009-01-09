<?php

/**
 * @DoctrineEntity
 */
class CmsPhonenumber
{
    /**
     * @DoctrineColumn(type="varchar", length=50)
     * @DoctrineId
     */
    public $phonenumber;
    /**
     * @DoctrineManyToOne(targetEntity="CmsUser", joinColumns={"user_id" = "id"})
     */
    public $user;

    public function setUser(CmsUser $user) {
        $this->user = $user;
        $user->addPhonenumber($this);
    }
}

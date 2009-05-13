<?php

namespace Doctrine\Tests\Models\CMS;

/**
 * @DoctrineEntity
 * @DoctrineTable(name="cms_phonenumbers")
 */
class CmsPhonenumber
{
    /**
     * @DoctrineColumn(type="string", length=50)
     * @DoctrineId
     */
    public $phonenumber;
    /**
     * @DoctrineManyToOne(targetEntity="CmsUser")
     * @DoctrineJoinColumn(name="user_id", referencedColumnName="id")
     */
    public $user;

    public function setUser(CmsUser $user) {
        $this->user = $user;
        $user->addPhonenumber($this);
    }
}

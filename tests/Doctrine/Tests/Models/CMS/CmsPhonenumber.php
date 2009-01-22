<?php

namespace Doctrine\Tests\Models\CMS;

/**
 * @DoctrineEntity(tableName="cms_phonenumbers")
 */
class CmsPhonenumber
{
    /**
     * @DoctrineColumn(type="varchar", length=50)
     * @DoctrineId
     */
    public $phonenumber;
    /**
     * @DoctrineManyToOne(targetEntity="Doctrine\Tests\Models\CMS\CmsUser",
            joinColumns={"user_id" = "id"})
     */
    public $user;

    public function setUser(CmsUser $user) {
        $this->user = $user;
        $user->addPhonenumber($this);
    }
}

<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\Tests\Models\CMS;

/**
 * Description of CmsAddress
 *
 * @author robo
 * @DoctrineEntity(tableName="cms_addresses")
 */
class CmsAddress
{
    /**
     * @DoctrineColumn(type="integer")
     * @DoctrineId
     * @DoctrineIdGenerator("auto")
     */
    public $id;
    /**
     * @DoctrineColumn(type="varchar", length=50)
     */
    public $country;
    /**
     * @DoctrineColumn(type="varchar", length=50)
     */
    public $zip;
    /**
     * @DoctrineColumn(type="varchar", length=50)
     */
    public $city;
    /**
     * @DoctrineOneToOne(
            targetEntity="Doctrine\Tests\Models\CMS\CmsUser",
            joinColumns={"user_id" = "id"})
     */
    public $user;
}


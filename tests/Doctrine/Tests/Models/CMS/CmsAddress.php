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
 * @DoctrineEntity
 * @DoctrineTable(name="cms_addresses")
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
     * @DoctrineOneToOne(targetEntity="CmsUser")
     * @DoctrineJoinColumn(name="user_id", referencedColumnName="id")
     */
    public $user;
}


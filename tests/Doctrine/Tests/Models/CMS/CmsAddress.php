<?php

namespace Doctrine\Tests\Models\CMS;

/**
 * CmsAddress
 *
 * @author Roman S. Borschel
 * @DoctrineEntity
 * @DoctrineTable(name="cms_addresses")
 */
class CmsAddress
{
    /**
     * @DoctrineColumn(type="integer")
     * @DoctrineId
     * @DoctrineGeneratedValue(strategy="auto")
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
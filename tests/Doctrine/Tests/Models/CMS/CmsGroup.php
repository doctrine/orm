<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\Tests\Models\CMS;

/**
 * Description of CmsGroup
 *
 * @author robo
 * @DoctrineEntity
 * @DoctrineTable(name="cms_groups")
 */
class CmsGroup
{
    /**
     * @DoctrineId
     * @DoctrineColumn(type="integer")
     * @DoctrineGeneratedValue(strategy="auto")
     */
    public $id;
    /**
     * @DoctrineColumn(type="varchar", length=50)
     */
    public $name;
    /**
     * @DoctrineManyToMany(targetEntity="CmsUser", mappedBy="groups")
     */
    public $users;
}


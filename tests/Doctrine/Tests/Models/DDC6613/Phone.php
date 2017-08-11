<?php
/**
 *
 * User: Uladzimir Struts <Sysaninster@gmail.com>
 * Date: 11.08.2017
 * Time: 13:12
 */

namespace Doctrine\Tests\Models\DDC6613;


/**
 * @Entity(readOnly=true)
 * @Table(name="ddc6613_phone")
 */

class Phone
{

    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

}
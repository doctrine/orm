<?php
/**
 *
 * User: Uladzimir Struts <Sysaninster@gmail.com>
 * Date: 11.08.2017
 * Time: 13:12
 */

namespace Doctrine\Tests\Models\DDC6613;


/**
 * @Table(name="ddc6613_phone")
 */

class Phone
{

    /**
     * @Id
     * @GeneratedValue(strategy="NONE")
     * @Column(type="integer")
     */
    public $id;

    public function __construct()
    {
        $this->id = uniqid('phone', true);
    }
}
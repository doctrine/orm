<?php
/**
 *
 * User: Uladzimir Struts <Sysaninster@gmail.com>
 * Date: 11.08.2017
 * Time: 13:12
 */

namespace Doctrine\Tests\Models\DDC6613;


use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity()
 * @Table(name="ddc6613_user")
 */
class User
{

    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;


    /**
     * @ManyToMany(targetEntity="Phone", fetch="LAZY", cascade={"remove", "detach"})
     */
    public $phones;

    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->phones = new ArrayCollection();
    }


}
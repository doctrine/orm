<?php

namespace Doctrine\Tests\Models\ECommerce;

use Doctrine\ORM\Annotation as ORM;

/**
 * ECommerceCustomer
 * Represents a registered user of a shopping application.
 *
 * @author Giorgio Sironi
 * @ORM\Entity
 * @ORM\Table(name="ecommerce_customers")
 */
class ECommerceCustomer
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $name;

    /**
     * @ORM\OneToOne(targetEntity="ECommerceCart", mappedBy="customer", cascade={"persist"})
     */
    private $cart;

    /**
     * Example of a one-one self referential association. A mentor can follow
     * only one customer at the time, while a customer can choose only one
     * mentor. Not properly appropriate but it works.
     *
     * @ORM\OneToOne(targetEntity="ECommerceCustomer", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(name="mentor_id", referencedColumnName="id")
     */
    private $mentor;

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setCart(ECommerceCart $cart)
    {
        if ($this->cart !== $cart) {
            $this->cart = $cart;
            $cart->setCustomer($this);
        }
    }

    /* Does not properly maintain the bidirectional association! */
    public function brokenSetCart(ECommerceCart $cart) {
        $this->cart = $cart;
    }

    public function getCart() {
        return $this->cart;
    }

    public function removeCart()
    {
        if ($this->cart !== null) {
            $cart = $this->cart;
            $this->cart = null;
            $cart->removeCustomer();
        }
    }

    public function setMentor(ECommerceCustomer $mentor)
    {
        $this->mentor = $mentor;
    }

    public function removeMentor()
    {
        $this->mentor = null;
    }

    public function getMentor()
    {
        return $this->mentor;
    }
}

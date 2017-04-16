<?php 
/**
 * Account
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="test\EcomBundle\Entity\AccountRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"user" = "User", "dealer" = "Dealer"})
 */
class Account
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255)
     */
    private $email;

	...
}


/**
 * User
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="test\EcomBundle\Entity\UserRepository")
 */
class User extends Account
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;
    ...
}


/**
 * Dealer
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="test\EcomBundle\Entity\DealerRepository")
 */
class Dealer extends  User
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     */
    private $type;
    ....
    }

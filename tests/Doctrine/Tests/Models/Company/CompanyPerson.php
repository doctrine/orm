<?php

namespace Doctrine\Tests\Models\Company;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Mapping;

/**
 * Description of CompanyPerson
 *
 * @author robo
 *
 * @ORM\Entity
 * @ORM\Table(name="company_persons")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({
 *      "person"    = "CompanyPerson",
 *      "manager"   = "CompanyManager",
 *      "employee"  = "CompanyEmployee"
 * })
 *
 * @ORM\NamedNativeQueries({
 *      @ORM\NamedNativeQuery(
 *          name           = "fetchAllWithResultClass",
 *          resultClass    = "__CLASS__",
 *          query          = "SELECT id, name, discr FROM company_persons ORDER BY name"
 *      ),
 *      @ORM\NamedNativeQuery(
 *          name            = "fetchAllWithSqlResultSetMapping",
 *          resultSetMapping= "mappingFetchAll",
 *          query           = "SELECT id, name, discr AS discriminator FROM company_persons ORDER BY name"
 *      )
 * })
 *
 * @ORM\SqlResultSetMappings({
 *      @ORM\SqlResultSetMapping(
 *          name    = "mappingFetchAll",
 *          entities= {
 *              @ORM\EntityResult(
 *                  entityClass         = "__CLASS__",
 *                  discriminatorColumn = "discriminator",
 *                  fields              = {
 *                      @ORM\FieldResult("id"),
 *                      @ORM\FieldResult("name"),
 *                  }
 *              )
 *          }
 *      )
 * })
 */
class CompanyPerson
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column
     */
    private $name;

    /**
     * @ORM\OneToOne(targetEntity="CompanyPerson")
     * @ORM\JoinColumn(name="spouse_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $spouse;

    /**
     * @ORM\ManyToMany(targetEntity="CompanyPerson")
     * @ORM\JoinTable(
     *     name="company_persons_friends",
     *     joinColumns={
     *         @ORM\JoinColumn(name="person_id", referencedColumnName="id", onDelete="CASCADE")
     *     },
     *     inverseJoinColumns={
     *         @ORM\JoinColumn(name="friend_id", referencedColumnName="id", onDelete="CASCADE")
     *     }
     * )
     */
    private $friends;

    public function __construct() {
        $this->friends = new \Doctrine\Common\Collections\ArrayCollection;
    }

    public function getId() {
        return  $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getSpouse() {
        return $this->spouse;
    }

    public function getFriends() {
        return $this->friends;
    }

    public function addFriend(CompanyPerson $friend) {
        if ( ! $this->friends->contains($friend)) {
            $this->friends->add($friend);
            $friend->addFriend($this);
        }
    }

    public function setSpouse(CompanyPerson $spouse) {
        if ($spouse !== $this->spouse) {
            $this->spouse = $spouse;
            $this->spouse->setSpouse($this);
        }
    }

    public static function loadMetadata(Mapping\ClassMetadata $metadata)
    {
        $tableMetadata = new Mapping\TableMetadata();
        $tableMetadata->setName('company_person');

        $metadata->setTable($tableMetadata);

        $metadata->addNamedNativeQuery(
            [
            'name'              => 'fetchAllWithResultClass',
            'query'             => 'SELECT id, name, discr FROM company_persons ORDER BY name',
            'resultClass'       => CompanyPerson::class,
            ]
        );

        $metadata->addNamedNativeQuery(
            [
            'name'              => 'fetchAllWithSqlResultSetMapping',
            'query'             => 'SELECT id, name, discr AS discriminator FROM company_persons ORDER BY name',
            'resultSetMapping'  => 'mappingFetchAll',
            ]
        );

        $metadata->addSqlResultSetMapping(
            [
            'name'      => 'mappingFetchAll',
            'columns'   => [],
            'entities'  => [
                [
                'fields' => [
                  [
                    'name'      => 'id',
                    'column'    => 'id',
                  ],
                  [
                    'name'      => 'name',
                    'column'    => 'name',
                  ],
                ],
                'entityClass' => CompanyPerson::class,
                'discriminatorColumn' => 'discriminator',
                ],
            ],
            ]
        );
    }
}


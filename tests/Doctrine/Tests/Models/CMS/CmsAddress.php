<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Mapping;

/**
 * CmsAddress
 *
 * @author Roman S. Borschel
 *
 * @ORM\Entity
 * @ORM\Table(name="cms_addresses")
 *
 * @ORM\NamedNativeQueries({
 *      @ORM\NamedNativeQuery(
 *          name                = "find-all",
 *          resultSetMapping    = "mapping-find-all",
 *          query               = "SELECT id, country, city FROM cms_addresses"
 *      ),
 *      @ORM\NamedNativeQuery(
 *          name           = "find-by-id",
 *          resultClass    = "CmsAddress",
 *          query          = "SELECT * FROM cms_addresses WHERE id = ?"
 *      ),
 *      @ORM\NamedNativeQuery(
 *          name            = "count",
 *          resultSetMapping= "mapping-count",
 *          query           = "SELECT COUNT(*) AS count FROM cms_addresses"
 *      )
 * })
 *
 * @ORM\SqlResultSetMappings({
 *      @ORM\SqlResultSetMapping(
 *          name    = "mapping-find-all",
 *          entities= {
 *              @ORM\EntityResult(
 *                  entityClass = "CmsAddress",
 *                  fields      = {
 *                      @ORM\FieldResult(name = "id",       column="id"),
 *                      @ORM\FieldResult(name = "city",     column="city"),
 *                      @ORM\FieldResult(name = "country",  column="country")
 *                  }
 *              )
 *          }
 *      ),
 *      @ORM\SqlResultSetMapping(
 *          name    = "mapping-without-fields",
 *          entities= {
 *              @ORM\EntityResult(
 *                  entityClass = "__CLASS__"
 *              )
 *          }
 *      ),
 *      @ORM\SqlResultSetMapping(
 *          name    = "mapping-count",
 *          columns = {
 *              @ORM\ColumnResult(
 *                  name = "count"
 *              )
 *          }
 *      )
 * })
 *
 * @ORM\EntityListeners({"CmsAddressListener"})
 */
class CmsAddress
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id @ORM\GeneratedValue
     */
    public $id;

    /**
     * @ORM\Column(length=50)
     */
    public $country;

    /**
     * @ORM\Column(length=50)
     */
    public $zip;

    /**
     * @ORM\Column(length=50)
     */
    public $city;

    /**
     * Testfield for Schema Updating Tests.
     */
    public $street;

    /**
     * @ORM\OneToOne(targetEntity="CmsUser", inversedBy="address")
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    public $user;

    public function getId() {
        return $this->id;
    }

    public function getUser() {
        return $this->user;
    }

    public function getCountry() {
        return $this->country;
    }

    public function getZipCode() {
        return $this->zip;
    }

    public function getCity() {
        return $this->city;
    }

    public function setUser(CmsUser $user) {
        if ($this->user !== $user) {
            $this->user = $user;
            $user->setAddress($this);
        }
    }

    public static function loadMetadata(Mapping\ClassMetadata $metadata)
    {
        $tableMetadata = new Mapping\TableMetadata();
        $tableMetadata->setName('company_person');

        $metadata->setPrimaryTable($tableMetadata);

        $fieldMetadata = new Mapping\FieldMetadata('id');

        $fieldMetadata->setType(Type::getType('integer'));
        $fieldMetadata->setPrimaryKey(true);

        $metadata->addProperty($fieldMetadata);

        $fieldMetadata = new Mapping\FieldMetadata('zip');

        $fieldMetadata->setType(Type::getType('string'));
        $fieldMetadata->setLength(50);

        $metadata->addProperty($fieldMetadata);

        $fieldMetadata = new Mapping\FieldMetadata('city');

        $fieldMetadata->setType(Type::getType('string'));
        $fieldMetadata->setLength(50);

        $metadata->addProperty($fieldMetadata);

        $joinColumns = [];

        $joinColumn = new Mapping\JoinColumnMetadata();

        $joinColumn->setReferencedColumnName('id');

        $joinColumns[] = $joinColumn;

        $association = new Mapping\OneToOneAssociationMetadata('user');

        $association->setJoinColumns($joinColumns);
        $association->setTargetEntity('CmsUser');

        $metadata->mapOneToOne($association);

        $metadata->addNamedNativeQuery(
            [
                'name'             => 'find-all',
                'query'            => 'SELECT id, country, city FROM cms_addresses',
                'resultSetMapping' => 'mapping-find-all',
            ]
        );

        $metadata->addNamedNativeQuery(
            [
                'name'        => 'find-by-id',
                'query'       => 'SELECT * FROM cms_addresses WHERE id = ?',
                'resultClass' => CmsAddress::class,
            ]
        );

        $metadata->addNamedNativeQuery(
            [
                'name'              => 'count',
                'query'             => 'SELECT COUNT(*) AS count FROM cms_addresses',
                'resultSetMapping'  => 'mapping-count',
            ]
        );

        $metadata->addSqlResultSetMapping(
            [
            'name'      => 'mapping-find-all',
            'columns'   => [],
            'entities'  => [
                [
                'fields' => [
                  [
                    'name'      => 'id',
                    'column'    => 'id',
                  ],
                  [
                    'name'      => 'city',
                    'column'    => 'city',
                  ],
                  [
                    'name'      => 'country',
                    'column'    => 'country',
                  ],
                ],
                'entityClass' => CmsAddress::class,
                ],
            ],
            ]
        );

        $metadata->addSqlResultSetMapping(
            [
            'name'      => 'mapping-without-fields',
            'columns'   => [],
            'entities'  => [
                [
                'entityClass' => CmsAddress::class,
                'fields' => []
                ]
            ]
            ]
        );

        $metadata->addSqlResultSetMapping(
            [
                'name' => 'mapping-count',
                'columns' => [
                    ['name' => 'count'],
                ]
            ]
        );

        $metadata->addEntityListener(\Doctrine\ORM\Events::postPersist, 'CmsAddressListener', 'postPersist');
        $metadata->addEntityListener(\Doctrine\ORM\Events::prePersist, 'CmsAddressListener', 'prePersist');

        $metadata->addEntityListener(\Doctrine\ORM\Events::postUpdate, 'CmsAddressListener', 'postUpdate');
        $metadata->addEntityListener(\Doctrine\ORM\Events::preUpdate, 'CmsAddressListener', 'preUpdate');

        $metadata->addEntityListener(\Doctrine\ORM\Events::postRemove, 'CmsAddressListener', 'postRemove');
        $metadata->addEntityListener(\Doctrine\ORM\Events::preRemove, 'CmsAddressListener', 'preRemove');

        $metadata->addEntityListener(\Doctrine\ORM\Events::preFlush, 'CmsAddressListener', 'preFlush');
        $metadata->addEntityListener(\Doctrine\ORM\Events::postLoad, 'CmsAddressListener', 'postLoad');
    }
}

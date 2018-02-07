<?php

declare(strict_types=1);

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
 *          resultClass    = CmsAddress::class,
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
 *                  entityClass = CmsAddress::class,
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
 * @ORM\EntityListeners({CmsAddressListener::class})
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
     * @ORM\OneToOne(targetEntity=CmsUser::class, inversedBy="address")
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    public $user;

    public function getId()
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function getZipCode()
    {
        return $this->zip;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function setUser(CmsUser $user)
    {
        if ($this->user !== $user) {
            $this->user = $user;
            $user->setAddress($this);
        }
    }
}

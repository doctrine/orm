<?php

namespace Doctrine\Tests\Models\CMS;

/**
 * CmsAddress
 *
 * @author Roman S. Borschel
 * @Entity
 * @Table(name="cms_addresses")
 *
 * @NamedNativeQueries({
 *      @NamedNativeQuery(
 *          name                = "find-all",
 *          resultSetMapping    = "mapping-find-all",
 *          query               = "SELECT id, country, city FROM cms_addresses"
 *      ),
 *      @NamedNativeQuery(
 *          name           = "find-by-id",
 *          resultClass    = "CmsAddress",
 *          query          = "SELECT * FROM cms_addresses WHERE id = ?"
 *      ),
 *      @NamedNativeQuery(
 *          name            = "count",
 *          resultSetMapping= "mapping-count",
 *          query           = "SELECT COUNT(*) AS count FROM cms_addresses"
 *      )
 * })
 *
 * @SqlResultSetMappings({
 *      @SqlResultSetMapping(
 *          name    = "mapping-find-all",
 *          entities= {
 *              @EntityResult(
 *                  entityClass = "CmsAddress",
 *                  fields      = {
 *                      @FieldResult(name = "id",       column="id"),
 *                      @FieldResult(name = "city",     column="city"),
 *                      @FieldResult(name = "country",  column="country")
 *                  }
 *              )
 *          }
 *      ),
 *      @SqlResultSetMapping(
 *          name    = "mapping-without-fields",
 *          entities= {
 *              @EntityResult(
 *                  entityClass = "__CLASS__"
 *              )
 *          }
 *      ),
 *      @SqlResultSetMapping(
 *          name    = "mapping-count",
 *          columns = {
 *              @ColumnResult(
 *                  name = "count"
 *              )
 *          }
 *      )
 * })
 *
 */
class CmsAddress
{
    /**
     * @Column(type="integer")
     * @Id @GeneratedValue
     */
    public $id;

    /**
     * @Column(length=50)
     */
    public $country;

    /**
     * @Column(length=50)
     */
    public $zip;

    /**
     * @Column(length=50)
     */
    public $city;

    /**
     * Testfield for Schema Updating Tests.
     */
    public $street;

    /**
     * @OneToOne(targetEntity="CmsUser", inversedBy="address")
     * @JoinColumn(referencedColumnName="id")
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
}
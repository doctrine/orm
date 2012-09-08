<?php

namespace Doctrine\Tests\Models\SingleTableInheritanceType;

/**
 * @Entity
 * @Table(name="users_followed_objects")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="object_type", type="smallint")
 * @DiscriminatorMap({4 = "UserFollowedUser", 3 = "UserFollowedStructure"})
 */
abstract class UserFollowedObject
{
    /**
     * @var integer $id
     *
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}

<?php

namespace Doctrine\Tests\Models\Detach;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="owner")
 */
class Owner {

    /**
     * @Id
     * @Column(type="integer")
     */
    public $id;

    /**
     * @ManyToMany(targetEntity="Member", cascade={"all"}, orphanRemoval=true)
     * @JoinTable(name="ownerHasMember",
     *      joinColumns={@JoinColumn(name="owner_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="member_id", referencedColumnName="id")}
     *      )
     */
    public $members;

    /**
     * @param array $members
     */
    public function __construct(int $id, array $members) {
        $this->id = $id;
        $this->members = new ArrayCollection();

        $this->changeMembers($members);
    }

    public function changeMembers(array $members) {
        $this->members->clear();

        foreach ($members as $member) {
            $this->members->add(new Member($member));
        }
    }

    public function getMembers(): array {
        return $this->members->map(
            function (Member $member): string {
                return $member->getName();
            }
        )->getValues();
    }

}

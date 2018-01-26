<?php

namespace Doctrine\Tests\Models\Quote;

/**
 * @Entity
 * @Table(name="`quote-user-data`")
 */
class UserData
{

	/**
	 * @Id
	 * @OneToOne(targetEntity="User")
	 * @JoinColumn(name="`user-id`", referencedColumnName="`user-id`", onDelete="CASCADE")
	 */
	public $user;

	/**
	 * @Column(type="string", name="`name`")
	 */
	public $name;


}

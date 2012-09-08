<?php

namespace Doctrine\Tests\Models\SingleTableInheritanceType;

/**
 * @Table(name="structures")
 * @Entity
 */
class Structure
{
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @Column(type="string", length=32, nullable=true)
	 */
	protected $name;
}

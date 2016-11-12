<?php

namespace Doctrine\Tests\Models\JoinedInheritanceType;

/**
 * @Entity
 * @DiscriminatorValue("another.child.class")
 */
class AnotherChildClass extends ChildClass
{
}
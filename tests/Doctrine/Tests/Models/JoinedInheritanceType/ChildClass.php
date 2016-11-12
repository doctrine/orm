<?php

namespace Doctrine\Tests\Models\JoinedInheritanceType;

/**
 * @MappedSuperclass
 * @DiscriminatorValue("custom_discriminator_value")
 */
abstract class ChildClass extends RootClass
{
}
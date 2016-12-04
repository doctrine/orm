<?php

namespace Doctrine\Tests\Models\ValueObjects;

/**
 * @Embeddable
 */
class NestedValueObject
{
  /**
   * @Embedded(class="Doctrine\Tests\Models\ValueObjects\ValueObject\ValueObject", columnPrefix=false)
   */
  public $nested;
}

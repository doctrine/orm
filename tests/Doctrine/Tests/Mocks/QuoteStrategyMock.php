<?php

namespace Doctrine\Tests\Mocks;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Mapping\AnsiQuoteStrategy;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Class QuoteStrategyMock
 *
 * This mock was created for the GH7262Test case.
 *
 * @author Michael Petri <mpetri@lyska.io>
 * @see \Doctrine\Tests\ORM\Functional\Ticket\GH7262Test
 *
 * @package Doctrine\Tests\Mocks
 */
final class QuoteStrategyMock extends AnsiQuoteStrategy
{

  /**
   * {@inheritdoc}
   */
  public function getTableName(ClassMetadata $class, AbstractPlatform $platform)
  {
    return $this->quote(parent::getTableName($class, $platform), $platform);
  }

  /**
   * {@inheritdoc}
   */
  public function getColumnName($fieldName, ClassMetadata $class, AbstractPlatform $platform)
  {
    return $this->quote(parent::getColumnName($fieldName, $class, $platform), $platform);
  }

  /**
   * {@inheritdoc}
   */
  public function getColumnAlias($columnName, $counter, AbstractPlatform $platform, ClassMetadata $class = NULL)
  {
    return $this->quote($columnName, $platform);
  }

  /**
   * Quotes a string depending on the current platform.
   *
   * @param string $string
   *   The string to quote.
   * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
   *   The current platform.
   *
   * @return string
   *   The quoted string if the platform could be detected.
   */
  private function quote(string $string, AbstractPlatform $platform)
  {

    switch ($platform->getName()) {
      case 'sqlite':
      case 'mysql':
        return "`{$string}`";
      case 'pgsql':
          return "'{$string}'";
      case 'mssql':
        return "[{$string}]";
    }

    return $string;
  }


}
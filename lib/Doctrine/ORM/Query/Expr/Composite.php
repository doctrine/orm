<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

use function implode;
use function is_object;
use function stripos;

/**
 * Expression class for building DQL and parts.
 */
class Composite extends Base
{
    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->count() === 1) {
            return (string) $this->parts[0];
        }

        $components = [];

        foreach ($this->parts as $part) {
            $components[] = $this->processQueryPart($part);
        }

        return implode($this->separator, $components);
    }

    /**
     * @param string $part
     *
     * @return string
     */
    private function processQueryPart($part)
    {
        $queryPart = (string) $part;

        if (is_object($part) && $part instanceof self && $part->count() > 1) {
            return $this->preSeparator . $queryPart . $this->postSeparator;
        }

        // Fixes DDC-1237: User may have added a where item containing nested expression (with "OR" or "AND")
        if (stripos($queryPart, ' OR ') !== false || stripos($queryPart, ' AND ') !== false) {
            return $this->preSeparator . $queryPart . $this->postSeparator;
        }

        return $queryPart;
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

use function str_repeat;

/**
 * A parse tree printer for Doctrine Query Language parser.
 *
 * @link        http://www.phpdoctrine.org
 */
class Printer
{
    /** Current indentation level */
    protected int $indent = 0;

    /**
     * Constructs a new parse tree printer.
     *
     * @param bool $silent Parse tree will not be printed if true.
     */
    public function __construct(protected bool $silent = false)
    {
    }

    /**
     * Prints an opening parenthesis followed by production name and increases
     * indentation level by one.
     *
     * This method is called before executing a production.
     *
     * @param string $name Production name.
     */
    public function startProduction(string $name): void
    {
        $this->println('(' . $name);
        $this->indent++;
    }

    /**
     * Decreases indentation level by one and prints a closing parenthesis.
     *
     * This method is called after executing a production.
     */
    public function endProduction(): void
    {
        $this->indent--;
        $this->println(')');
    }

    /**
     * Prints text indented with spaces depending on current indentation level.
     *
     * @param string $str The text.
     */
    public function println(string $str): void
    {
        if (! $this->silent) {
            echo str_repeat('    ', $this->indent), $str, "\n";
        }
    }
}

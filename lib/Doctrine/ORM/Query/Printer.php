<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

use function str_repeat;

/**
 * A parse tree printer for Doctrine Query Language parser.
 */
class Printer
{
    /**
     * Current indentation level
     *
     * @var int
     */
    protected $indent = 0;

    /**
     * Defines whether parse tree is printed (default, false) or not (true).
     *
     * @var bool
     */
    protected $silent;

    /**
     * Constructs a new parse tree printer.
     *
     * @param bool $silent Parse tree will not be printed if true.
     */
    public function __construct($silent = false)
    {
        $this->silent = $silent;
    }

    /**
     * Prints an opening parenthesis followed by production name and increases
     * indentation level by one.
     *
     * This method is called before executing a production.
     *
     * @param string $name Production name.
     */
    public function startProduction($name)
    {
        $this->println('(' . $name);
        $this->indent++;
    }

    /**
     * Decreases indentation level by one and prints a closing parenthesis.
     *
     * This method is called after executing a production.
     */
    public function endProduction()
    {
        $this->indent--;
        $this->println(')');
    }

    /**
     * Prints text indented with spaces depending on current indentation level.
     *
     * @param string $str The text.
     */
    public function println($str)
    {
        if (! $this->silent) {
            echo str_repeat('    ', $this->indent), $str, "\n";
        }
    }
}

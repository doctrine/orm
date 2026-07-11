<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

/**
 * An EncryptedQuerySetMapping describes which bind parameters of an SQL query target encrypted
 * fields and therefore must be encrypted before execution.
 *
 * <b>Same as ResultSetMapping, Users should use the public methods.</b>
 *
 * @see ResultSetMapping
 */
class EncryptedQuerySetMapping
{
    /**
     * Parameter positions (int) or column names (string), mapped to the encrypted field
     * they are compared to or assigned to, as [declaring class, field name] tuples.
     *
     * @ignore
     * @var array<int|string, array{class-string, string}>
     */
    public array $encryptedParameters = [];

    /** @param class-string $class */
    public function addEncryptedParameter(int|string $position, string $class, string $field): void
    {
        $this->encryptedParameters[$position] = [$class, $field];
    }

    /**
     * @param list<int|string> $positions
     * @param class-string     $class
     */
    public function addEncryptedParameters(array $positions, string $class, string $field): void
    {
        $tuple = [$class, $field];

        foreach ($positions as $position) {
            $this->encryptedParameters[$position] = $tuple;
        }
    }

    /** @return array<int|string, array{class-string, string}> */
    public function getEncryptedParameters(): array
    {
        return $this->encryptedParameters;
    }

    public function isEmpty(): bool
    {
        return $this->encryptedParameters === [];
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\ORM\Encrypt;

/**
 * List all filtering supported for Encrypt.
 * Possible to compare using query on an encrypted field of an entity.
 */
enum EncryptQuery: string
{
    /**
     * Support Equality compare on a deterministic encrypted field.
     *
     * e.g. SELECT * FROM user WHERE encrypted_email = :email
     */
    case Equality = 'equality';
}

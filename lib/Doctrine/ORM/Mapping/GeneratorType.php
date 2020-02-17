<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

final class GeneratorType
{
    /**
     * NONE means the class does not have a generated id. That means the class
     * must have a natural, manually assigned id.
     */
    public const NONE = 'NONE';

    /**
     * AUTO means the generator type will depend on what the used platform prefers.
     * Offers full portability.
     */
    public const AUTO = 'AUTO';

    /**
     * SEQUENCE means a separate sequence object will be used. Platforms that do
     * not have native sequence support may emulate it. Full portability is currently
     * not guaranteed.
     */
    public const SEQUENCE = 'SEQUENCE';

    /**
     * TABLE means a separate table is used for id generation.
     * Offers full portability.
     */
    public const TABLE = 'TABLE';

    /**
     * IDENTITY means an identity column is used for id generation. The database
     * will fill in the id column on insertion. Platforms that do not support
     * native identity columns may emulate them. Full portability is currently
     * not guaranteed.
     */
    public const IDENTITY = 'IDENTITY';

    /**
     * CUSTOM means that customer will use own ID generator that supposedly work
     */
    public const CUSTOM = 'CUSTOM';

    /**
     * Will break upon instantiation.
     */
    private function __construct()
    {
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\ORM\Id;

use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use LogicException;

use function get_debug_type;
use function sprintf;

abstract class AbstractIdGenerator
{
    /** @var bool */
    private $alreadyDelegatedToGenerateId = false;

    /**
     * Generates an identifier for an entity.
     *
     * @deprecated Call {@see generateId()} instead.
     *
     * @param object|null $entity
     *
     * @return mixed
     */
    public function generate(EntityManager $em, $entity)
    {
        if ($this->alreadyDelegatedToGenerateId) {
            throw new LogicException(sprintf(
                'Endless recursion detected in %s. Please implement generateId() without calling the parent implementation.',
                get_debug_type($this)
            ));
        }

        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9325',
            '%s::generate() is deprecated, call generateId() instead.',
            get_debug_type($this)
        );

        $this->alreadyDelegatedToGenerateId = true;

        try {
            return $this->generateId($em, $entity);
        } finally {
            $this->alreadyDelegatedToGenerateId = false;
        }
    }

    /**
     * Generates an identifier for an entity.
     *
     * @param object|null $entity
     *
     * @return mixed
     */
    public function generateId(EntityManagerInterface $em, $entity)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9325',
            'Not implementing %s in %s is deprecated.',
            __FUNCTION__,
            get_debug_type($this)
        );

        if (! $em instanceof EntityManager) {
            throw new InvalidArgumentException('Unsupported entity manager implementation.');
        }

        return $this->generate($em, $entity);
    }

    /**
     * Gets whether this generator is a post-insert generator which means that
     * {@link generateId()} must be called after the entity has been inserted
     * into the database.
     *
     * By default, this method returns FALSE. Generators that have this requirement
     * must override this method and return TRUE.
     *
     * @return bool
     */
    public function isPostInsertGenerator()
    {
        return false;
    }
}

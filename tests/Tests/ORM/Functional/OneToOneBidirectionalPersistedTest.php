<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\Tests\Models\Jedi\JediKnight;
use Doctrine\Tests\OrmFunctionalTestCase;

final class OneToOneBidirectionalPersistedTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('jedi');

        parent::setUp();
    }

    public function testInverseColumnIsPopulated(): void
    {
        $master  = new JediKnight('Obi-Wan Kenobi');
        $padawan = new JediKnight('Anakin Skywalker');

        $padawan->master = $master;
        $master->padawan = $padawan;

        $this->_em->persist($master);
        $this->_em->persist($padawan);
        $this->_em->flush();

        $tableContent = $this->_em->getConnection()->fetchAllAssociative(
            'SELECT id, name, master_id, padawan_id FROM jedi_knights WHERE id IN (:ids) ORDER BY name',
            ['ids' => [$master->id, $padawan->id]],
            ['ids' => ArrayParameterType::INTEGER],
        );

        self::assertSame(
            [
                ['id' => $padawan->id, 'name' => 'Anakin Skywalker', 'master_id' => $master->id, 'padawan_id' => null],
                ['id' => $master->id, 'name' => 'Obi-Wan Kenobi', 'master_id' => null, 'padawan_id' => $padawan->id],
            ],
            $tableContent,
        );
    }
}

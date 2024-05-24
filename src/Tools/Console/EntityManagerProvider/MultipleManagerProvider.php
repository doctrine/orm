<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Console\EntityManagerProvider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\UnknownManagerException;

final class MultipleManagerProvider implements EntityManagerProvider
{
	/**
	 * @param array<string, EntityManagerInterface> $managers
	 */
	public function __construct(
		private readonly array $managers,
        private readonly string $defaultManagerName = 'default',
	) {
	}

	public function getDefaultManager(): EntityManagerInterface
	{
        if (array_key_exists($this->defaultManagerName, $this->managers)) {
            return $this->managers[$this->defaultManagerName];
        } else {
            throw UnknownManagerException::unknownManager($this->defaultManagerName, $this->getManagerNames());
        }
	}

	public function getManager(string $name): EntityManagerInterface
	{
		$managerNamesAvailable = $this->getManagerNames();
		if (in_array($name, $managerNamesAvailable)) {
			return $this->managers[$name];
		} else {
			throw UnknownManagerException::unknownManager($name, $managerNamesAvailable);
		}
	}

	private function getManagerNames(): array
	{
		return array_keys($this->managers);
	}
}

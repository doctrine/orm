<?php

namespace PHPSTORM_META {

	/** Argument sets */

	registerArgumentsSet('queryHydrate',
		\Doctrine\ORM\AbstractQuery::HYDRATE_OBJECT, \Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY, \Doctrine\ORM\AbstractQuery::HYDRATE_SCALAR, \Doctrine\ORM\AbstractQuery::HYDRATE_SINGLE_SCALAR,
		\Doctrine\ORM\AbstractQuery::HYDRATE_SIMPLEOBJECT
	);

	registerArgumentsSet('lockMode',
		\Doctrine\DBAL\LockMode::NONE, \Doctrine\DBAL\LockMode::OPTIMISTIC, \Doctrine\DBAL\LockMode::PESSIMISTIC_READ,
		\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE
	);

	registerArgumentsSet('paramTypes',
		\Doctrine\DBAL\Types\Type::TARRAY, \Doctrine\DBAL\Types\Type::SIMPLE_ARRAY, \Doctrine\DBAL\Types\Type::JSON_ARRAY,
		\Doctrine\DBAL\Types\Type::JSON, \Doctrine\DBAL\Types\Type::BIGINT, \Doctrine\DBAL\Types\Type::BOOLEAN,
		\Doctrine\DBAL\Types\Type::DATETIME, \Doctrine\DBAL\Types\Type::DATETIME_IMMUTABLE, \Doctrine\DBAL\Types\Type::DATETIMETZ,
		\Doctrine\DBAL\Types\Type::DATETIMETZ_IMMUTABLE, \Doctrine\DBAL\Types\Type::DATE, \Doctrine\DBAL\Types\Type::DATE_IMMUTABLE,
		\Doctrine\DBAL\Types\Type::TIME, \Doctrine\DBAL\Types\Type::TIME_IMMUTABLE, \Doctrine\DBAL\Types\Type::DECIMAL, \Doctrine\DBAL\Types\Type::INTEGER,
		\Doctrine\DBAL\Types\Type::OBJECT, \Doctrine\DBAL\Types\Type::SMALLINT, \Doctrine\DBAL\Types\Type::STRING, \Doctrine\DBAL\Types\Type::TEXT,
		\Doctrine\DBAL\Types\Type::BINARY, \Doctrine\DBAL\Types\Type::BLOB, \Doctrine\DBAL\Types\Type::FLOAT,
		\Doctrine\DBAL\Types\Type::GUID, \Doctrine\DBAL\Types\Type::DATEINTERVAL,

		\PDO::PARAM_NULL, \PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_LOB, \PDO::PARAM_STMT, \PDO::PARAM_BOOL, \PDO::PARAM_INPUT_OUTPUT,
		\PDO::PARAM_STR_CHAR, \PDO::PARAM_STR_NATL
	);

	registerArgumentsSet('queryFetchTypes',
		\Doctrine\ORM\Mapping\ClassMetadata::FETCH_LAZY, \Doctrine\ORM\Mapping\ClassMetadata::FETCH_EAGER, \Doctrine\ORM\Mapping\ClassMetadata::FETCH_EXTRA_LAZY
	);

	registerArgumentsSet('dqlPart',
		'distinct', 'select', 'from', 'join', 'set', 'where', 'groupBy', 'having', 'orderBy'
	);

	/** EntityManager */

	// ::find()
	override(\Doctrine\Common\Persistence\ObjectManager::find(), type(0));

	// ::newHydrator()
	expectedArguments(\Doctrine\ORM\EntityManagerInterface::newHydrator(), 0, argumentsSet('queryHydrate'));
	// TODO: phpstorm bug, remove in future
	expectedArguments(\Doctrine\ORM\EntityManager::newHydrator(), 0, argumentsSet('queryHydrate'));

	// ::lock()
	expectedArguments(\Doctrine\ORM\EntityManagerInterface::lock(), 1, argumentsSet('lockMode'));
	// TODO: phpstorm bug, remove in future
	expectedArguments(\Doctrine\ORM\EntityManager::lock(), 1, argumentsSet('lockMode'));

	// ::copy()
	override(\Doctrine\ORM\EntityManagerInterface::copy(), type(0));

	// ::getReference()
	override(\Doctrine\ORM\EntityManagerInterface::getReference(), type(0));

	// ::getPartialReference()
	override(\Doctrine\ORM\EntityManagerInterface::getPartialReference(), type(0));

	// ::merge()
	override(\Doctrine\ORM\EntityManagerInterface::merge(), type(0));

	/** AbstractQuery */

	// ::getResult()
	expectedArguments(\Doctrine\ORM\AbstractQuery::getResult(), 0, argumentsSet('queryHydrate'));

	// ::execute()
	expectedArguments(\Doctrine\ORM\AbstractQuery::execute(), 1, argumentsSet('queryHydrate'));

	// ::setParameter()
	expectedArguments(\Doctrine\ORM\AbstractQuery::setParameter(), 2, argumentsSet('paramTypes'));
	expectedArguments(\Doctrine\ORM\QueryBuilder::setParameter(), 2, argumentsSet('paramTypes'));

	// ::setFetchMode()
	expectedArguments(\Doctrine\ORM\AbstractQuery::setFetchMode(), 2, argumentsSet('queryFetchTypes'));

	// ::setHydrationMode()
	expectedArguments(\Doctrine\ORM\AbstractQuery::setHydrationMode(), 0, argumentsSet('queryHydrate'));

	// ::getHydrationMode()
	expectedReturnValues(\Doctrine\ORM\AbstractQuery::getHydrationMode(), argumentsSet('queryHydrate'));

	/** QueryBuilder|Query */

	// dqlParts
	expectedArguments(\Doctrine\ORM\QueryBuilder::getDQLPart(), 0, argumentsSet('dqlPart'));
	expectedArguments(\Doctrine\ORM\QueryBuilder::resetDQLPart(), 0, argumentsSet('dqlPart'));
	expectedArguments(\Doctrine\ORM\QueryBuilder::add(), 0, argumentsSet('dqlPart'));

	// ::getType()
	expectedReturnValues(\Doctrine\ORM\QueryBuilder::getType(), \Doctrine\ORM\QueryBuilder::SELECT, \Doctrine\ORM\QueryBuilder::DELETE, \Doctrine\ORM\QueryBuilder::UPDATE);

	// ::setCacheMode()
	registerArgumentsSet('cacheModes', \Doctrine\ORM\Cache::MODE_GET, \Doctrine\ORM\Cache::MODE_PUT, \Doctrine\ORM\Cache::MODE_NORMAL, \Doctrine\ORM\Cache::MODE_REFRESH);
	expectedArguments(\Doctrine\ORM\QueryBuilder::setCacheMode(), 0, argumentsSet('cacheModes'));
	expectedArguments(\Doctrine\ORM\Query::setCacheMode(), 0, argumentsSet('cacheModes'));

	// ::getCacheMode()
	expectedReturnValues(\Doctrine\ORM\Query::getCacheMode(), argumentsSet('cacheModes'));
	expectedReturnValues(\Doctrine\ORM\QueryBuilder::getCacheMode(), argumentsSet('cacheModes'));

	// ::getState()
	expectedReturnValues(\Doctrine\ORM\QueryBuilder::getState(), \Doctrine\ORM\QueryBuilder::STATE_DIRTY, \Doctrine\ORM\QueryBuilder::STATE_CLEAN);

}
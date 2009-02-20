<?php

namespace Doctrine\DBAL;

/**
 * Driver interface.
 * Interface that all DBAL drivers must implement.
 *
 * @since 2.0
 */
interface Driver
{
    /**
     * Attempts to create a connection with the database.
     *
     * @param array $params All connection parameters passed by the user.
     * @param string $username The username to use when connecting.
     * @param string $password The password to use when connecting.
     * @param array $driverOptions The driver options to use when connecting.
     * @return Doctrine::DBAL::Connection The database connection.
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = array());

    /**
     * Gets the DatabasePlatform instance that provides all the metadata about
     * the platform this driver connects to.
     *
     * @return Doctrine::DBAL::DatabasePlatform The database platform.
     */
    public function getDatabasePlatform();

    /**
     * Gets the SchemaManager that can be used to inspect and change the underlying
     * database schema of the platform this driver connects to.
     *
     * @return Doctrine\DBAL\SchemaManager
     */
    public function getSchemaManager(Connection $conn);
}
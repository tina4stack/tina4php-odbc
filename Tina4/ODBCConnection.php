<?php
/**
 * Tina4 - This is not a 4ramework.
 * Copy-right 2007 - current Tina4
 * License: MIT https://opensource.org/licenses/MIT
 */

namespace Tina4;

/**
 * Connection class for ODBC
 */
class ODBCConnection
{
    /**
     * Database connection
     * @var false|resource
     */
    private $connection;

    /**
     * Creates an ODBC Database Connection
     * @param string $connectionString Connection string
     * @param string $username database username
     * @param string $password password of the user
     */
    public function __construct(string $connectionString, string $username, string $password)
    {
        $this->connection = odbc_connect($connectionString, $username, $password,SQL_CUR_USE_DRIVER);
    }

    /**
     * Returns a databse connection or false if failed
     * @return false|resource
     */
    final public function getConnection()
    {
        return $this->connection;
    }
}

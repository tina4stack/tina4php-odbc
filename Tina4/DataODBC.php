<?php
/**
 * Tina4 - This is not a 4ramework.
 * Copy-right 2007 - current Tina4
 * License: MIT https://opensource.org/licenses/MIT
 */

namespace Tina4;

/**
 * ODBC driver for Tina4
 */
class DataODBC implements \Tina4\DataBase
{
    use DataBaseCore;

    /**
     * @param string|null $database
     * @param string|null $username
     * @param string|null $password
     * @param string $dateFormat
     */
    public function __construct(?string $database, ?string $username = "", ?string $password = "", string $dateFormat = "Y-m-d")
    {
        global $cache;

        if (!empty($cache)) {
            $this->cache = $cache;
        }

        $this->databaseName = $database;
        $this->username = $username;
        $this->password = $password;
        $this->dateFormat = $dateFormat;

        $this->open();
    }

    /**
     * @return mixed
     */
    public function open()
    {
        if (!function_exists("odbc_connect")) {
            throw new \Exception("ODBC extension for PHP needs to be installed");
        }

        $this->dbh = (new ODBCConnection(
            $this->databaseName,
            $this->username,
            $this->password
        ))->getConnection();
    }

    /**
     * @return mixed
     */
    public function close()
    {
        odbc_close($this->dbh);
    }

    /**
     * @return array|mixed
     */
    public function exec()
    {
        $params = $this->parseParams(func_get_args());
        $params = $params["params"];

        return (new ODBCExec($this))->exec($params, null);
    }

    /**
     * @return string
     */
    final public function getLastId(): string
    {
        return "";
    }

    /**
     * @param string|string $tableName
     * @return bool
     */
    final public function tableExists(string $tableName): bool
    {
        $data = odbc_tables($this->dbh, null, null, $tableName);
        $database = [];
        while ($table = odbc_fetch_array($data)) {
            break;
        }

        return !empty($table);
    }

    /**
     * @param string $sql
     * @param int $noOfRecords
     * @param int $offSet
     * @param array $fieldMapping
     * @return DataResult|null
     */
    final public function fetch($sql = "", int $noOfRecords = 10, int $offSet = 0, array $fieldMapping = []): ?DataResult
    {
        return (new ODBCQuery($this))->query($sql, $noOfRecords, $offSet, $fieldMapping);
    }

    /**
     * @param null $transactionId
     * @return mixed
     */
    final public function commit($transactionId = null)
    {
        return odbc_commit($this->dbh);
    }

    /**
     * @param null $transactionId
     * @return mixed
     */
    final public function rollback($transactionId = null)
    {
        return odbc_rollback($this->dbh);
    }

    /**
     * @param bool|bool $onState
     */
    final public function autoCommit(bool $onState = true): void
    {
        odbc_autocommit($this->dbh, $onState);
    }

    /**
     * @return string
     */
    final public function startTransaction()
    {
        return "Resource id #0";
    }

    /**
     * @return bool
     */
    final public function error()
    {
        $errorCode = odbc_error($this->dbh);

        if (!$errorCode) {
            return (new DataError(0, "None"));
        } else {
            $errorMessage = odbc_errormsg($this->dbh);
            return (new DataError($errorCode, $errorMessage));
        }
    }

    /**
     * Gets the metadata
     * @return array
     */
    final public function getDatabase(): array
    {
        if (!empty($this->databaseMetaData)) {
            return $this->databaseMetaData;
        }

        $this->databaseMetaData = (new ODBCMetaData($this))->getDatabaseMetaData();

        return $this->databaseMetaData;
    }

    /**
     * @return string
     */
    final public function getDefaultDatabaseDateFormat(): string
    {
        return "Y-m-d";
    }

    /**
     * @return int|null
     */
    final public function getDefaultDatabasePort(): ?int
    {
        return null; //Depends on the ODBC driver
    }

    /**
     * @param string|string $fieldName
     * @param int|int $fieldIndex
     * @return string
     */
    final public function getQueryParam(string $fieldName, int $fieldIndex): string
    {
        return "?";
    }

    /**
     * @return bool
     */
    final public function isNoSQL(): bool
    {
        return false;
    }
}
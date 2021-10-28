<?php
/**
 * Tina4 - This is not a 4ramework.
 * Copy-right 2007 - current Tina4
 * License: MIT https://opensource.org/licenses/MIT
 */

namespace Tina4;

use Tina4\DataBaseCore;

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
        $this->dbh = odbc_connect($this->databaseName, $this->username, $this->password,SQL_CUR_USE_DRIVER);
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

        $preparedQuery =  odbc_prepare($this->dbh, $params[0]);

        $error = $this->error();

        if (!empty($preparedQuery) && $error->getError()["errorCode"] === 0) {
            $params[0] = $preparedQuery;

            if ( count( $params ) !== 1 ) {
                odbc_execute( ...$params );
            } else {
                odbc_execute( $params[0] );
            }

            $error = $this->error();
        }

        return $error;
    }

    /**
     * @return string
     */
    public function getLastId(): string
    {
        // TODO: Implement getLastId() method.
    }

    /**
     * @param string|string $tableName
     * @return bool
     */
    public function tableExists(string $tableName): bool
    {
        $data = odbc_tables($this->dbh, null, null, $tableName);
        $database = [];
        while ($table = odbc_fetch_array($data)) {
            break;
        }

        return !empty($table);
    }

    /**
     * @param string|string $sql
     * @param int|int $noOfRecords
     * @param int|int $offSet
     * @param array $fieldMapping
     * @return \Tina4\DataResult|null
     */
    public function fetch(string $sql = "", int $noOfRecords = 10, int $offSet = 0, array $fieldMapping = []): ?\Tina4\DataResult
    {
        // TODO: Implement fetch() method.
    }

    /**
     * @param null $transactionId
     * @return mixed
     */
    public function commit($transactionId = null)
    {
        return odbc_commit($this->dbh);
    }

    /**
     * @param null $transactionId
     * @return mixed
     */
    public function rollback($transactionId = null)
    {
        return odbc_rollback($this->dbh);
    }

    /**
     * @param bool|bool $onState
     */
    public function autoCommit(bool $onState = true): void
    {
        // TODO: Implement autoCommit() method.
    }

    /**
     * @return string
     */
    public function startTransaction()
    {
        // TODO: Implement startTransaction() method.
    }

    /**
     * @return bool
     */
    public function error()
    {
        $errorMessage = odbc_error($this->dbh);

        if (!$errorMessage) {
            return (new DataError(0, "None"));
        } else {
            return (new DataError(9999, $errorMessage));
        }
    }

    /**
     * @return array|mixed
     */
    public function getDatabase(): array
    {
        $data = odbc_tables($this->dbh);
        $database = [];
        while ($table = odbc_fetch_array($data)) {
            $fieldData = odbc_exec($this->dbh, "select * from `{$table["TABLE_NAME"]}` where 1 = 2");
            $ncols = odbc_num_fields($fieldData);
            $fields = [];
            for ($n=1; $n<=$ncols; $n++) {
                $fields[$n-1] = odbc_field_name($fieldData, $n);
            }

            $columns = odbc_columns($this->dbh, "%", '%', $table["TABLE_NAME"], '%');
            $tid = 0;

            while (($row = odbc_fetch_array($columns))) {
                $database[trim($table["TABLE_NAME"])][$tid]["column"] = $fields[$tid];
                $database[trim($table["TABLE_NAME"])][$tid]["field"] = strtolower($fields[$tid]);
                $database[trim($table["TABLE_NAME"])][$tid]["description"] = "";
                $database[trim($table["TABLE_NAME"])][$tid]["type"] = $row["SQL_NO_NULLS"];
                $database[trim($table["TABLE_NAME"])][$tid]["length"] = $row["LENGTH"];
                $database[trim($table["TABLE_NAME"])][$tid]["precision"] =  $row["PRECISION"];
                $database[trim($table["TABLE_NAME"])][$tid]["default"] = "-";
                $database[trim($table["TABLE_NAME"])][$tid]["notnull"] = "-";
                $database[trim($table["TABLE_NAME"])][$tid]["pk"] = "-";
                $tid++;
            }
        }
        return $database;
    }

    /**
     * @return string
     */
    public function getDefaultDatabaseDateFormat(): string
    {
        return "Y-m-d";
    }

    /**
     * @return int|null
     */
    public function getDefaultDatabasePort(): ?int
    {
        return null; //Depends on the ODBC driver
    }

    /**
     * @param string|string $fieldName
     * @param int|int $fieldIndex
     * @return string|mixed
     */
    public function getQueryParam(string $fieldName, int $fieldIndex): string
    {
        // TODO: Implement getQueryParam() method.
    }

    /**
     * @return bool
     */
    public function isNoSQL(): bool
    {
        return false;
    }
}
<?php
/**
 * Tina4 - This is not a 4ramework.
 * Copy-right 2007 - current Tina4
 * License: MIT https://opensource.org/licenses/MIT
 */

namespace Tina4;

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
            unset($params[0]);

            if ( count( $params ) > 0 ) {
                odbc_execute($preparedQuery, $params); //a,d,v
            } else {
                odbc_execute( $preparedQuery );
            }

            $error = $this->error();
        }

        return $error;
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
     * @param string|string $sql
     * @param int|int $noOfRecords
     * @param int|int $offSet
     * @param array $fieldMapping
     * @return \Tina4\DataResult|null
     */
    final public function fetch(string $sql = "", int $noOfRecords = 10, int $offSet = 0, array $fieldMapping = []): ?\Tina4\DataResult
    {
        if (stripos($sql, "execute") === false) {
            $countRecords = odbc_exec($this->dbh, "select count(*) as count from (" . $sql . ") t");
            $countRecords = odbc_fetch_array($countRecords)["count"];
            $sql .= " limit {$offSet},{$noOfRecords}";
        } else {
            $countRecords = 1;
        }

        $recordCursor = odbc_exec($this->dbh, $sql);
        $records = [];
        if (!empty($recordCursor)) {
            while ($recordArray = odbc_fetch_array($recordCursor)) {
                if (!empty($recordArray)) {
                    $records[] = (new DataRecord($recordArray, $fieldMapping, $this->getDefaultDatabaseDateFormat(), $this->dateFormat));
                }
            }
        }

        if (!empty($records)) {
            //populate the fields
            $fid = 1;
            $fields = [];
            foreach ($records[0] as $field => $value) {
                $fields[] = (new DataField($fid, odbc_field_name($recordCursor, $fid), odbc_field_name($recordCursor, $fid), odbc_field_type($recordCursor, $fid)));
                $fid++;
            }
        } else {
            $records = null;
            $fields = null;
        }


        $error = $this->error();

        return (new DataResult($records, $fields, $countRecords, $offSet, $error));
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
     * @return array|mixed
     */
    final public function getDatabase(): array
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
     * @return string|mixed
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
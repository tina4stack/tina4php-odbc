<?php
/**
 * Tina4 - This is not a 4ramework.
 * Copy-right 2007 - current Tina4
 * License: MIT https://opensource.org/licenses/MIT
 */

namespace Tina4;

/**
 * Gets the metadata from an ODBC connection
 */
class ODBCMetaData extends DataConnection implements DataBaseMetaData
{

    /**
     * Gets a list of tables
     * @return array
     */
    final public function getTables(): array
    {
        $data = odbc_tables($this->getDbh());
        $tables = [];
        while ($table = odbc_fetch_array($data)) {
            $tables[] = (object)["tableName" => $table["TABLE_NAME"]];
        }

        return $tables;
    }

    /**
     * Get the primary keys @todo Implement
     * @param string $tableName
     * @return array
     */
    final public function getPrimaryKeys(string $tableName): array
    {
        return [];
    }

    /**
     * Gets the foreign keys @todo Implement
     * @param string $tableName
     * @return array
     */
    final public function getForeignKeys(string $tableName): array
    {
        return [];
    }

    /**
     * @param string $tableName
     * @return array
     */
    final public function getTableInformation(string $tableName): array
    {
        $fieldData = odbc_exec($this->getDbh(), "select * from `$tableName` where 1 = 2");
        $nCols = odbc_num_fields($fieldData);
        $fields = [];
        for ($n=1; $n <= $nCols; $n++) {
            $fields[$n-1] = odbc_field_name($fieldData, $n);
        }

        $columns = odbc_columns($this->getDbh(), "%", '%', $tableName, '%');
        $tid = 0;

        $tableInformation = [];

        while (($columnData = odbc_fetch_array($columns))) {

            $fieldData = new \Tina4\DataField(
                $tid,
                strtolower(trim($fields[$tid])),
                strtolower(trim($fields[$tid])),
                trim($columnData["SQL_NO_NULLS"]),
                (int)trim($columnData["LENGTH"]),
                (int)trim($columnData["PRECISION"])
            );

            $fieldData->description = "";
            $fieldData->isNotNull = false;
            $fieldData->isPrimaryKey = false;
            $fieldData->isForeignKey = false;
            $fieldData->defaultValue = null;
            $tableInformation[] = $fieldData;

            $tid++;
        }

        return $tableInformation;
    }

    /**
     * @return array
     */
    final public function getDatabaseMetaData(): array
    {
        $database = [];
        $tables = $this->getTables();

        foreach ($tables as $record) {
            $tableInfo = $this->getTableInformation($record->tableName);

            $database[strtolower($record->tableName)] = $tableInfo;
        }

        return $database;
    }
}
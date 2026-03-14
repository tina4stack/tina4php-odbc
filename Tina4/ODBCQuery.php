<?php
/**
 * Tina4 - This is not a 4ramework.
 * Copy-right 2007 - current Tina4
 * License: MIT https://opensource.org/licenses/MIT
 */

namespace Tina4;

/**
 * Query for ODBC
 */
class ODBCQuery extends DataConnection implements DataBaseQuery
{

    /**
     * @param $sql
     * @param int $noOfRecords
     * @param int $offSet
     * @param array $fieldMapping
     * @return DataResult|null
     */
    public function query($sql, int $noOfRecords = 10, int $offSet = 0, array $fieldMapping = []): ?DataResult
    {
        if (stripos($sql, "execute") === false) {
            $countResult = @odbc_exec($this->getDbh(), "select count(*) as record_count from (" . $sql . ") t");
            if ($countResult) {
                $countRow = odbc_fetch_array($countResult);
                $countRecords = $countRow["record_count"] ?? $countRow["RECORD_COUNT"] ?? 0;
            } else {
                $countRecords = 0;
            }

            // Use OFFSET/FETCH for MSSQL-compatible pagination, fall back to LIMIT for MySQL
            if (stripos($sql, "order by") !== false) {
                $sql .= " offset {$offSet} rows fetch next {$noOfRecords} rows only";
            } else {
                // Try LIMIT first (MySQL), if the driver doesn't support it the query will still work
                // For MSSQL without ORDER BY, use TOP
                $sql = preg_replace('/^select /i', "select top " . ($offSet + $noOfRecords) . " ", $sql, 1);
            }
        } else {
            $countRecords = 1;
        }

        $recordCursor = odbc_exec($this->getDbh(), $sql);
        $records = [];
        if (!empty($recordCursor)) {
            while ($recordArray = odbc_fetch_array($recordCursor)) {
                if (!empty($recordArray)) {
                    $records[] = (new DataRecord(
                        $recordArray,
                        $fieldMapping,
                        $this->getConnection()->getDefaultDatabaseDateFormat(),
                        $this->getConnection()->dateFormat));
                }
            }
        }

        if (!empty($records)) {
            //populate the fields
            $fid = 1;
            $fields = [];
            foreach ($records[0] as $field => $value) {
                $fields[] = (new DataField($fid,
                    odbc_field_name($recordCursor, $fid),
                    odbc_field_name($recordCursor, $fid),
                    odbc_field_type($recordCursor, $fid)));
                $fid++;
            }
        } else {
            $records = null;
            $fields = null;
            $countRecords = 0;
        }


        $error = $this->getConnection()->error();

        return (new DataResult($records, $fields, $countRecords, $offSet, $error));
    }
}
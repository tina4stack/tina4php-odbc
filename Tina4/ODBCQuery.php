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
            $countRecords = odbc_exec($this->getDbh(), "select count(*) as count from (" . $sql . ") t");
            $countRecords = odbc_fetch_array($countRecords)["count"];
            $sql .= " limit {$offSet},{$noOfRecords}";
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
        }


        $error = $this->getConnection()->error();

        return (new DataResult($records, $fields, $countRecords, $offSet, $error));
    }
}
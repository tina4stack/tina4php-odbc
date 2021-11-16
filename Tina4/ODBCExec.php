<?php
/**
 * Tina4 - This is not a 4ramework.
 * Copy-right 2007 - current Tina4
 * License: MIT https://opensource.org/licenses/MIT
 */

namespace Tina4;

/**
 * Execute a query using ODBC driver
 */
class ODBCExec extends DataConnection implements DataBaseExec
{

    /**
     * Executes a query
     * @param $params
     * @param $tranId
     * @return mixed
     */
    final public function exec($params, $tranId)
    {
        $preparedQuery =  odbc_prepare($this->getDbh(), $params[0]);

        $error = $this->getConnection()->error();

        if (!empty($preparedQuery) && $error->getError()["errorCode"] === 0) {
            unset($params[0]);

            if ( count( $params ) > 0 ) {
                odbc_execute($preparedQuery, $params); //a,d,v
            } else {
                odbc_execute( $preparedQuery );
            }

            $error = $this->getConnection()->error();
        }

        return $error;
    }
}
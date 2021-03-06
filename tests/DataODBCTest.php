<?php
/**
 * Tina4 - This is not a 4ramework.
 * Copy-right 2007 - current Tina4
 * License: MIT https://opensource.org/licenses/MIT
 */

use PHPUnit\Framework\TestCase;

require_once "./Tina4/DataODBC.php";

class DataODBCTest extends TestCase
{
    public $connectionString;
    public $DBA;

    final function setUp(): void
    {
        $this->connectionString = "DRIVER={/Library/ODBC/Actual Open Source Databases.bundle/Contents/MacOS/atopnsrc.so};SERVER=localhost;PORT=33306;DATABASE=testing";
        $this->DBA = new \Tina4\DataODBC($this->connectionString, "root", "pass1234");
    }

    final function testOpen(): void
    {
        $this->assertNotEmpty($this->DBA);
    }



    final function testTableExists() : void
    {
        $exists = $this->DBA->tableExists("user");
        $this->assertIsBool($exists, "Not working");
        $exists = $this->DBA->tableExists("user_one");
        $this->assertEquals(false, $exists, "Not working false table check");
    }

    final function testDropCreateTable() : void
    {
        $error = $this->DBA->exec("drop table if exists testing");

        $error = $this->DBA->exec("create table testing(id integer default 0, primary key(id))");

        $exists = $this->DBA->tableExists("testing");
        $this->assertEquals(true, $exists, "Not working false table check");
    }

    final function testRead(): void
    {
        $this->DBA->exec("insert into testing (id) values (?)", 1);

        $this->DBA->exec("insert into testing (id) values (2)");

        $records = $this->DBA->fetch("select * from testing")->asArray();

        $this->assertCount(2, $records, "Records were not 2");
    }

    final function testGetDatabase(): void
    {
        $database = $this->DBA->getDatabase();
        $this->assertArrayHasKey("testing", $database);
    }
}
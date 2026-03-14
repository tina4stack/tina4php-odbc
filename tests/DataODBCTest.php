<?php
/**
 * Tina4 - This is not a 4ramework.
 * Copy-right 2007 - current Tina4
 * License: MIT https://opensource.org/licenses/MIT
 *
 * Uniform database driver test suite.
 * Start the database:  docker compose up -d  (uses MySQL on port 33066)
 * Run tests:           composer test
 */

use PHPUnit\Framework\TestCase;

require_once "./Tina4/DataODBC.php";

class DataODBCTest extends TestCase
{
    public $connectionString;
    public $DBA;

    final public function setUp(): void
    {
        // ODBC connecting to MySQL via ODBC Driver
        // Windows: uses MySQL ODBC 8.0 ANSI/Unicode Driver
        // Fallback: uses generic SQL Server ODBC or any available driver
        $driver = "";
        if (PHP_OS_FAMILY === "Windows") {
            // Try MySQL ODBC driver first, then ODBC Driver 18 for SQL Server against MSSQL
            $driver = "{ODBC Driver 18 for SQL Server}";
            $this->connectionString = "DRIVER={$driver};SERVER=localhost,1433;DATABASE=master;UID=sa;PWD=Tina1234!;TrustServerCertificate=yes";
        } else {
            $driver = "{MySQL ODBC 8.0 Unicode Driver}";
            $this->connectionString = "DRIVER={$driver};SERVER=localhost;PORT=33066;DATABASE=test;";
        }

        $this->DBA = new \Tina4\DataODBC($this->connectionString, "", "");
    }

    // --- Connection ---

    final public function testOpen(): void
    {
        $this->assertNotEmpty($this->DBA, "Database connection should not be empty");
    }

    final public function testGetShortName(): void
    {
        $this->assertEquals("odbc", $this->DBA->getShortName());
    }

    final public function testIsNotNoSQL(): void
    {
        $this->assertFalse($this->DBA->isNoSQL());
    }

    // --- Table operations ---

    final public function testDropCreateTable(): void
    {
        if ($this->DBA->tableExists("sub_testing")) {
            $this->DBA->exec("drop table sub_testing");
        }
        if ($this->DBA->tableExists("testing")) {
            $this->DBA->exec("drop table testing");
        }

        $this->DBA->exec("create table testing (
            id integer default 0,
            name varchar(200) default 'Name',
            contact_number varchar(20) default '',
            age integer default 22,
            salary numeric(10,2),
            primary key(id)
        )");

        $exists = $this->DBA->tableExists("testing");
        $this->assertTrue($exists, "Table 'testing' should exist after creation");
    }

    final public function testTableExistsTrue(): void
    {
        $this->assertTrue($this->DBA->tableExists("testing"), "Table 'testing' should exist");
    }

    final public function testTableExistsFalse(): void
    {
        $this->assertFalse($this->DBA->tableExists("nonexistent_table_xyz"), "Non-existent table should return false");
    }

    // --- CRUD operations ---

    final public function testInsertWithParams(): void
    {
        $this->DBA->exec("insert into testing (id, name) values (?, ?)", 1, "Alice");
        $record = $this->DBA->fetch("select * from testing where id = 1")->asArray();
        $this->assertEquals("Alice", $record[0]["name"] ?? $record[0]["NAME"] ?? "", "Name should be 'Alice'");
    }

    final public function testInsertWithoutParams(): void
    {
        $this->DBA->exec("insert into testing (id, name) values (2, 'Bob')");
        $record = $this->DBA->fetch("select * from testing where id = 2")->asArray();
        $this->assertEquals("Bob", $record[0]["name"] ?? $record[0]["NAME"] ?? "", "Name should be 'Bob'");
    }

    final public function testFetchAll(): void
    {
        $records = $this->DBA->fetch("select * from testing")->asArray();
        $this->assertGreaterThanOrEqual(2, count($records), "Should have at least 2 records");
    }

    final public function testFetchWithLimit(): void
    {
        $result = $this->DBA->fetch("select * from testing", 1, 0);
        $this->assertLessThanOrEqual(1, count($result->records()), "Should return at most 1 record");
    }

    final public function testFetchEmpty(): void
    {
        $result = $this->DBA->fetch("select * from testing where id = 99999");
        $this->assertCount(0, $result->records(), "Should return 0 records for non-existent id");
    }

    final public function testUpdate(): void
    {
        $this->DBA->exec("update testing set name = 'Updated' where id = 1");
        $record = $this->DBA->fetch("select * from testing where id = 1")->asArray();
        $this->assertEquals("Updated", $record[0]["name"] ?? $record[0]["NAME"] ?? "", "Name should be 'Updated' after update");
    }

    final public function testDelete(): void
    {
        $this->DBA->exec("insert into testing (id, name) values (100, 'ToDelete')");
        $this->DBA->exec("delete from testing where id = 100");
        $result = $this->DBA->fetch("select * from testing where id = 100");
        $this->assertCount(0, $result->records(), "Deleted record should not exist");
    }

    final public function testFieldNameConversion(): void
    {
        $this->DBA->exec("insert into testing (id, contact_number) values (50, '0836464535')");
        $record = $this->DBA->fetch("select * from testing where id = 50")->asArray();
        $this->assertEquals("0836464535", $record[0]["contactNumber"] ?? $record[0]["contact_number"] ?? "", "snake_case should convert to camelCase");
    }

    // --- Metadata ---

    final public function testGetDatabase(): void
    {
        $database = $this->DBA->getDatabase();
        $this->assertArrayHasKey("testing", $database, "Metadata should contain 'testing' table");
    }

    final public function testGetDefaultDateFormat(): void
    {
        $format = $this->DBA->getDefaultDatabaseDateFormat();
        $this->assertNotEmpty($format, "Date format should not be empty");
    }
}

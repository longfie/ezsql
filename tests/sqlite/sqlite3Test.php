<?php

namespace ezsql\Tests\sqlite;

use Exception;
use ezsql\Database;
use ezsql\Config;
use ezsql\Database\ez_sqlite3;
use ezsql\Tests\EZTestCase;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2018-03-08 at 02:54:12.
 */
class sqlite3Test extends EZTestCase
{
    /**
     * constant string path and file name of the SQLite test database
     */
    const TEST_SQLITE_DB = 'ez_test.sqlite3';
    const TEST_SQLITE_DB_DIR = './tests/sqlite/';
    
    /**
     * @var ez_sqlite3
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
	{
        if (!extension_loaded('sqlite3')) {
            $this->markTestSkipped(
              'The sqlite3 Lib is not available.'
            );
        }
        
        $this->object = Database::initialize('sqlite3', [self::TEST_SQLITE_DB_DIR, self::TEST_SQLITE_DB]); 
        $this->object->prepareOn();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        $this->object->drop("test_table");
        $this->object = null;
    }

    /**
     * @covers ezsql\Database\ez_sqlite3::settings
     */
    public function testSettings()
    {
        $this->assertTrue($this->object->settings() instanceof \ezsql\ConfigInterface);    
    } 

    /**
     * @covers ezsql\Database\ez_sqlite3::disconnect
     * @covers ezsql\Database\ez_sqlite3::reset
     * @covers ezsql\Database\ez_sqlite3::handle
     */
    public function testDisconnect() 
    {
        $this->object->connect();
        $this->assertTrue($this->object->isConnected());
        $this->assertNotNull($this->object->handle());
        $this->object->disconnect();
        $this->assertFalse($this->object->isConnected());
        $this->object->reset();
        $this->assertNull($this->object->handle());
    } // testDisconnect

    /**
     * @covers ezsql\Database\ez_sqlite3::connect
     */
    public function testConnect() 
    {      
        $this->assertTrue($this->object->connect());    
        $this->assertTrue($this->object->isConnected());
    } // testSQLiteConnect

    /**
     * @covers ezsql\Database\ez_sqlite3::quick_connect
     */
    public function testQuick_connect() 
    {
        $this->assertNotNull($this->object->quick_connect(self::TEST_SQLITE_DB_DIR, self::TEST_SQLITE_DB));
    } // testSQLiteQuick_connect
    
    /**
     * @covers ezsql\Database\ez_sqlite3::escape
     */
    public function testSQLite3Escape() 
    {
        $this->object->connect(self::TEST_SQLITE_DB_DIR, self::TEST_SQLITE_DB);
        $result = $this->object->escape("This is'nt escaped.");
        $this->assertEquals("This is''nt escaped.", $result);         
    } // testSQLiteEscape
    
    /**
     * @covers ezsql\Database\ez_sqlite3::sysDate
     */
    public function testSysDate() 
    {
        $this->assertEquals('now', $this->object->sysDate());
    }

    /**
     * @covers ezsql\Database\ez_sqlite3::query
     */
    public function testQuery()
    {
        $this->object->connect(self::TEST_SQLITE_DB_DIR, self::TEST_SQLITE_DB);
        // Create a table..
        $this->object->drop("test_table");
        $this->assertEquals(0,$this->object->query("CREATE TABLE test_table ( MyColumnA INTEGER PRIMARY KEY, MyColumnB TEXT(32) );"));

        // Insert test data
        for($i=0;$i<3;++$i)
        {
            $this->assertNotNull($this->object->query('INSERT INTO test_table (MyColumnB) VALUES ("'.md5(microtime()).'");'));
        }
	
        // Get list of tables from current database..
        $my_tables = $this->object->get_results("SELECT * FROM sqlite_master WHERE sql NOTNULL;");
        
        // Loop through each row of results..
        foreach ( $my_tables as $table )
        {
            // Get results of DESC table..
            $this->assertNotNull($this->object->get_results("SELECT * FROM $table->name;"));
        }

        // Get rid of the table we created..
        $this->object->query("DROP TABLE test_table;");
    }

    /**
     * @covers ezsql\ezQuery::create
     */
    public function testCreate()
    {
        $this->object->connect(self::TEST_SQLITE_DB_DIR, self::TEST_SQLITE_DB);
        $this->assertEquals($this->object->create('new_create_test',
            column('id', INTEGERS, notNULL, AUTO),
            column('create_key', VARCHAR, 50),
            primary('id_pk', 'id')), 
        0);

        $this->object->prepareOff();
        $this->assertEquals($this->object->insert('new_create_test',
            ['create_key' => 'test 2']),
        0);
        $this->object->prepareOn();
    }

    /**
     * @covers ezsql\ezQuery::drop
     */
    public function testDrop()
    {
        $this->assertEquals($this->object->drop('new_create_test'), 0);
    }
    
    /**
     * @covers ezsql\ezQuery::insert
     */
    public function testInsert()
    {
        $this->object->query('CREATE TABLE test_table(id integer, test_key varchar(50), PRIMARY KEY (ID))');

        $result = $this->object->insert('test_table', array('test_key'=>'test 1' ));
        $this->assertEquals(0, $result);
    }
       
    /**
     * @covers ezsql\ezQuery::update
     */
    public function testUpdate()
    {
        $this->object->query('CREATE TABLE test_table(id integer, test_key varchar(50), test_value varchar(50), PRIMARY KEY (ID))');

        $this->object->insert('test_table', array('test_key'=>'test 1', 'test_value'=>'testing string 1' ));
        $this->object->insert('test_table', array('test_key'=>'test 2', 'test_value'=>'testing string 2' ));
        $result = $this->object->insert('test_table', array('test_key'=>'test 3', 'test_value'=>'testing string 3' ));
        $this->assertEquals($result, 3);

        $test_table['test_key'] = 'the key string';
        $where="test_key  =  test 1";
        $this->assertEquals(1, $this->object->update('test_table', $test_table, $where));

        $this->assertEquals(1, $this->object->update('test_table', 
            $test_table,
            eq('test_key','test 3', _AND),
            eq('test_value','testing string 3'))
        );

        $where=eq('test_value','testing string 4');
        $this->assertEquals(0, $this->object->update('test_table', $test_table, $where));

        $this->assertEquals(1, $this->object->update('test_table', $test_table, "test_key  =  test 2"));
    }
    
    /**
     * @covers ezsql\ezQuery::delete
     */
    public function testDelete()
    {
        $this->object->query('CREATE TABLE test_table(id integer, test_key varchar(50), test_value varchar(50), PRIMARY KEY (ID))');

        $this->object->insert('test_table', array('test_key'=>'test 1', 'test_value'=>'testing string 1' ));
        $this->object->insert('test_table', array('test_key'=>'test 2', 'test_value'=>'testing string 2' ));
        $this->object->insert('test_table', array('test_key'=>'test 3', 'test_value'=>'testing string 3' ));   

        $where=array('test_key', '=', 'test 1');
        $this->assertEquals($this->object->delete('test_table', $where), 1);
        
        $this->assertEquals($this->object->delete('test_table', 
            array('test_key', '=', 'test 3'),
            array('test_value','=', 'testing string 3')), 1);

        $where = array('test_value', '=', 'testing 2');
        $this->assertEquals(0, $this->object->delete('test_table', $where));

        $where = "test_key  =  test 2";
        $this->assertEquals(1, $this->object->delete('test_table', $where));
    }  

    /**
     * @covers ezsql\ezQuery::selecting
     * @covers ezsql\Database\ez_sqlite3::query
     * @covers ezsql\Database\ez_sqlite3::prepareValues
     * @covers ezsql\Database\ez_sqlite3::query_prepared
     */
    public function testSelecting()
    {
        $this->object->query('CREATE TABLE test_table(id integer, test_key varchar(50), test_value varchar(50), PRIMARY KEY (ID))');

        $this->object->insert('test_table', array('test_key'=>'test 1', 'test_value'=>'testing string 1' ));
        $this->object->insert('test_table', array('test_key'=>'test 2', 'test_value'=>'testing string 2' ));
        $this->object->insert('test_table', array('test_key'=>'test 3', 'test_value'=>'testing string 3' ));   
        
        $result = $this->object->selecting('test_table'); 
               
        $i = 1;
        foreach ($result as $row) {
            $this->assertEquals($i, $row->id);
            $this->assertEquals('testing string ' . $i, $row->test_value);
            $this->assertEquals('test ' . $i, $row->test_key);
            ++$i;
        }
        
        $where = eq('id',2);
        $result = $this->object->selecting('test_table', 'id', $this->object->where($where));
        foreach ($result as $row) {
            $this->assertEquals(2, $row->id);
        }
        
        $where = [ eq('test_value','testing string 3') ];
        $result = $this->object->selecting('test_table', 'test_key', $this->object->where($where));
        foreach ($result as $row) {
            $this->assertEquals('test 3', $row->test_key);
        }      
        
        $result = $this->object->selecting('test_table', 'test_value', $this->object->where(eq( 'test_key','test 1' )));
        foreach ($result as $row) {
            $this->assertEquals('testing string 1', $row->test_value);
        }
    } 
    
    /**
     * @covers ezsql\Database\ez_sqlite3::__construct
     */
    public function test__Construct_Error() {
        $this->expectExceptionMessageRegExp('/[Missing configuration details]/');
        $this->assertNull(new ez_sqlite3());
    }

    /**
     * @covers ezsql\Database\ez_sqlite3::__construct
     */
    public function test__construct() {
        unset($GLOBALS['ez'.\SQLITE3]);
        $settings = new Config('sqlite3', [self::TEST_SQLITE_DB_DIR, self::TEST_SQLITE_DB]);
        $this->assertNotNull(new ez_sqlite3($settings));
    } 
}

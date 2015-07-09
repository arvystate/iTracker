<?
/******************************************************************************
*                                                                             *
* MySQL database                                                              *
*                                                                             *
*******************************************************************************
*                                                                             *
* MySQL class, to use with any application necessary to use MySQL database.   *
* Anything can be added to it, new methods or more. Contains everything       *
* required in a php based application.                                        *
*                                                                             *
*******************************************************************************
*                                                                             *
* Legoless - 16.12.2006 - Class released                                      *
* Legoless - 07.01.2007 - SQL Data fix, now returns correct result            *
* Legoless - 08.04.2007 - KOC mod for untrainedSold                           *
* Legoless - 23.04.2007 - SQL fix when number of rows is 0                    *
* Legoless - 11.10.2007 - Table prefix support added                          *
* Legoless - 28.10.2007 - Query counter added                                 *
* Legoless - 24.12.2007 - Table postfix support added, prefix error fixed     *
* Legoless - 26.12.2007 - Added support for using multiple tables in queries  *
* Legoless - 21.01.2008 - Updating functions can use prefixes                 *
* Legoless - 09.02.2008 - Where clause and update parse bugs fixed            *
* Legoless - 10.02.2008 - Small fix with blank or null quotes                 *
* Legoless - 12.03.2008 - Added truncate function                             *
*                                                                             *
*******************************************************************************
*                                                                             *
* Usage (public functions):                                                   *
*                                                                             *
* sql_open($sql_server, $sql_user, ...) - Opens database connection           *
* sql_close() - Closes last opened database connection                        *
* sql_execute() - Executes and sorts query set in $this->query                *
* sql_select($tables, $condition, $fields) - Returns data from database       *
* sql_update($tables, $condition, $update) - Updates database                 *
* sql_insert($tables, $fields, $values) - Inserts new row in database         *
* sql_delete($tables, $condition) - Deletes rows in database                  *
* sql_truncate($table) - Truncates (erases) all data in selected table        *
* sql_query_count() - Returns number of queries sent to database              *
*                                                                             *
*******************************************************************************
*                                                                             *
* Notes:                                                                      *
*                                                                             *
* Using sql_execute() and $query function requires the prefix to be manually  *
* entered with query, the class will NOT add prefix to table in query.        *
*                                                                             *
* Class is to be initialized with: $some_variable = new sql_db()              *
*                                                                             *
* If prefix and postfix are blank, class will work with normal tables         *
*                                                                             *
*******************************************************************************
*                                                                             *
* This program is free software: you can redistribute it and/or modify it     *
* under the terms of the GNU General Public License as published by the Free  *
* Software Foundation, either version 3 of the License, or (at your option)   *
* any later version.                                                          *
*                                                                             *
* This program is distributed in the hope that it will be useful,but WITHOUT  *
* ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or       *
* FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for    *
* more details.                                                               *
*                                                                             *
* You should have received a copy of the GNU General Public License along     *
* with this program.  If not, see <http://www.gnu.org/licenses/>.             *
*                                                                             *
******************************************************************************/

if(!defined('IN_APP'))
{
	die('This page does not exist.');
}

class sql_db
{

	//
	// Private variables (server configuration)
	//
	
	private $server; //Server address
	private $user; //Server username
	private $password; //Server password
	private $dbname; //Server database name
	private $prefix; //Server table prefix
	private $postfix; //Server table postfix
	private $persistency; //Define persistent connection

	private $db_connect_id; //Variable stores MySQL connection ID
	private $db_query_count; //Stores the count of queries
	
	//
	// Public use variables
	//

	public $query; //Variable stores MySQL query
	public $result; //Variable stores MySQL result (if query was successful)
	public $data; //Variable stores a sorted data

	//
	// Construct/Open database connection
	//
	
	public function sql_open($sql_server, $sql_user, $sql_password, $sql_dbname, $sql_persistency = true, $sql_tableprefix = '', $sql_tablepostfix = '')
	{
		// Storing details
		
		$this->server = $sql_server;
		$this->user = $sql_user;
		$this->password = $sql_password;
		$this->dbname = $sql_dbname;
		$this->prefix = $sql_tableprefix;
		$this->postfix = $sql_tablepostfix;
		$this->persistency = $persistency;
		
		// Starting query counter
		
		$this->db_query_count = 0;

		// Creating connection to database

		if($this->persistency)
		{
			$this->db_connect_id = @mysql_pconnect($this->server, $this->user, $this->password);
		}
		else
		{
			$this->db_connect_id = @mysql_connect($this->server, $this->user, $this->password);
		}
		
		// Selecting the database
		
		if($this->db_connect_id)
		{
			if($this->dbname != "")
			{
				$dbselect = @mysql_select_db($this->dbname);
				if(!$dbselect)
				{
					@mysql_close($this->db_connect_id);
					$this->db_connect_id = $dbselect;
				}
			}
			return $this->db_connect_id;
		}
		else
		{
			return;
		}
	}

	//
	// Close the database connection
	//
	
	public function sql_close()
	{
		if($this->db_connect_id)
		{
			if($this->query)
			{
				unset($this->query);
			}

			if($this->result)
			{
				@mysql_free_result($this->result);
			}
			
			if($this->data)
			{
				unset($this->data);
			}	
						
			$result = @mysql_close($this->db_connect_id);
			return $result;
		}
		else
		{
			return;
		}
	}

	//
	// Returns data from database
	//
	
	public function sql_select($tables, $condition, $fields = '*')
	{
		$fields = $this->sql_field_construct($fields);
		$tables = $this->sql_tableline_construct($tables);
		$condition = $this->sql_field_construct($condition);
	
		$this->query = 'select ' . $fields . ' from ' . $tables . ' where ' . $condition;
		
		$this->sql_execute();
		
		if($this->data == '')
		{
			return;
		}
		else
		{
			return $this->data;
		}
	}

	//
	// Updates data in database
	//
	
	public function sql_update($tables, $condition, $update)
	{
		$tables = $this->sql_tableline_construct($tables);
		$condition = $this->sql_field_construct($condition);
		$update = $this->sql_update_construct($update);
	
		$this->query = 'update ' . $tables . ' set ' . $update . ' where ' . $condition;
				
		$this->sql_execute();
		
		if($this->data == '')
		{
			return;
		}
		else
		{
			return $this->data;
		}
	}
	
	//
	// Inserts new data in database
	//
	
	public function sql_insert($tables, $fields, $values)
	{
		$tables = $this->sql_tableline_construct($tables);
		$fields = $this->sql_field_construct($fields);
	
		$this->query = 'insert into ' . $tables . ' (' . $fields . ') values (' . $values . ')';
		
		$this->sql_execute();
		
		if($this->data == '')
		{
			return;
		}
		else
		{
			return $this->data;
		}
	}
	
	//
	// Deletes data in database
	//
	
	public function sql_delete($tables, $condition)
	{	
		$tables = $this->sql_tableline_construct($tables);
		$condition = $this->sql_field_construct($condition);
	
		$this->query = 'delete from ' . $tables . ' where ' . $condition;
		
		$this->sql_execute();
		
		if($this->data == '')
		{
			return;
		}
		else
		{
			return $this->data;
		}
	}
	
	//
	// Erases all data in table
	//
	
	public function sql_truncate($table)
	{
		$table = $this->sql_tableline_construct($table);
		
		$this->query = 'truncate table ' . $table;
		
		$this->sql_execute();
		
		if($this->data == '')
		{
			return;
		}
		else
		{
			return $this->data;
		}
	}
	
	//
	// Executes MySQL query
	//
	
	public function sql_execute()
	{	
		if ($this->result)
		{
			@mysql_free_result($this->result);
			$this->result = '';
		}
		
		if ($this->data)
		{
			$this->data = '';
		}
	
		if ($this->query)
		{
			$this->result = @mysql_query( $this->query, $this->db_connect_id );
			$this->db_query_count = $this->db_query_count + 1;
		}
		else
		{
			return $this->data;
		}
		
		if (!$this->result)
		{
			return $this->data;
		}
		
		$data_check = explode (' ', $this->query);
		
		if ( ($data_check[0] == 'select') && ($data_check[1] == 'count(*)') )
		{
			$query_type = 2;
		}
		elseif ($data_check[0] == 'select')
		{
			$query_type = 1;
		}
		else
		{
			$query_type = 0;
		}
	
		$this->sql_sort($query_type);
		
		if($this->data == '')
		{
			return;
		}
		else
		{
			return $this->data;
		}
	}
	
	//
	// Sorts data from MySQL result into an array or returns the database result
	//
	
	private function sql_sort($query_type = 0)
	{
		if (!$this->result)
		{
			$this->data = '';
		}
		elseif (@mysql_num_rows($this->result) == 0)
		{
			$this->data = '';
		}
		elseif ($query_type == 1)
		{
			$this->data = '';
			$count = 0;
			while ( $row = @mysql_fetch_object($this->result) )
			{			
				$row->untrainedSold = floor($row->untrainedSold);
				$this->data[$count] = $row;
				$count++;
			}
		}
		elseif ($query_type == 2)
		{
			$this->data = @mysql_fetch_array($this->result);
			$this->data = $this->data[0];
		}
		else
		{
			$this->data = $this->result;
		}
		
		if($this->data == '')
		{
			return;
		}
		else
		{
			return $this->data;
		}
	}
	
	//
	// Returns number of queries sent to MySQL server
	//
	
	public function sql_query_count()
	{
		return $this->db_query_count;
	}
	
	//
	// Creates tables with prefixes and postfixes
	//
	
	private function sql_tableline_construct($tables)
	{
		// No spaces between prefix & postfix and table name
		$tables = str_replace(' ', '', $tables);
		
		$tables = explode(',', $tables);
		$tableline = '';
		
		for ($i = 0; $i < count($tables); $i++)
		{
			$tableline .= $this->prefix . $tables[$i] . $this->postfix;
			
			// Adding comma between tables
			if($i != (count($tables) - 1) )
			{
				$tableline .= ',';
			}
		}
		
		return $tableline;		
	}
	
	//
	// Creates fields that use different tables with prefixes and postfixes
	//
	
	private function sql_field_construct($condition)
	{
		// Preparing to save quotes
		$quote_base = array();
		
		$quote = false;
		
		$new_condition = '';
		
		// Going through query character by character
		for($i = 0, $x = 0; $i < strlen($condition); $i++)
		{
			// If we're not in quote
			if($quote == false)
			{
				// Checking character for quote, constructing new update with {DATA<quote_number>}
				if(ord($condition{$i}) == 39)
				{
					$quote = true;
					$new_condition .= '{DATA' . $x . '}';
				}
				// Continuing to create the query
				else
				{
					$new_condition .= $condition{$i};
				}
			}
			// We're currently located inside SQL quotes
			else
			{
				// If we're ordered to skip next character using the \
				if(ord($condition{$i}) == 92)
				{
					$quote_base[$x] .= $condition{$i};
					$i++;
					$quote_base[$x] .= $condition{$i};
				}
				// End of the quote
				elseif(ord($condition{$i}) == 39)
				{
					// Fixes bug of empty quotes
					$quote_base[$x] .= ' ';
					
					$quote = false;
					$x++;
				}
				// We're collecting the quotes into $quote_base
				else
				{
					$quote_base[$x] .= $condition{$i};
				}
			}
		}
		
		// Quotes removed from condition clause
		$condition = $new_condition;
		
		// Divide each word by space
		$condition = explode(' ', $condition);
						
		for($i = 0; $i < count($condition); $i++)
		{
			// Incase there is a single word of two compared different table fields
			$sub_condition = explode('=', $condition[$i]);

			// Going through both compared fields
			for($x = 0; $x < count($sub_condition); $x++)
			{
				if(strpos($sub_condition[$x], '\'') !== false)
				{
					continue;
				}
								
				$sub_field = explode('.', $sub_condition[$x]);
				
				// Replacing the table with prefixes only if there is table in the field
				if(count($sub_field) == 2)
				{
					$sub_field[0] = $this->prefix . $sub_field[0] . $this->postfix;
				}
				
				// Joining parsed tables
				$sub_condition[$x] = implode('.', $sub_field);
			}
			
			// Joining parsed fields
			$condition[$i] = implode('=', $sub_condition);			
		}
		
		// Constructing parsed condition
		$condition = implode(' ', $condition);
		
		// Replacing collected quotes back with DATA
		for($i = 0; $i < count($quote_base); $i++)
		{
			$condition = str_replace('{DATA' . $i . '}', chr(39) . substr($quote_base[$i], 0, strlen($quote_base[$i]) - 1) . chr(39), $condition);
		}
		
		return $condition;
	}
	
	//
	// Creates update query that use different tables with prefixes and postfixes
	//
	
	private function sql_update_construct ($update)
	{
		// Quote parsing switch
		$quote = false;
	
		// Removing quoted data from query
		$new_update = '';
	
		$quote_base = array();
		
		// Going through query character by character
		for($i = 0, $x = 0; $i < strlen($update); $i++)
		{
			// If we're not in quote
			if($quote == false)
			{
				// Checking character for quote, constructing new update with {DATA<quote_number>}
				if(ord($update{$i}) == 39)
				{
					$quote = true;
					$new_update .= '{DATA' . $x . '}';
				}
				// Continuing to create the query
				else
				{
					$new_update .= $update{$i};
				}
			}
			// We're currently located inside SQL quotes
			else
			{
				// If we're ordered to skip next character using the \
				if(ord($update{$i}) == 92)
				{
					$quote_base[$x] .= $update{$i};
					$i++;
					$quote_base[$x] .= $update{$i};
				}
				// End of the quote
				elseif(ord($update{$i}) == 39)
				{
					// Fixes bug of empty quotes
					$quote_base[$x] .= ' ';
					
					$quote = false;
					$x++;
				}
				// We're collecting the quotes into $quote_base
				else
				{
					$quote_base[$x] .= $update{$i};					
				}
			}
		}

		// Removing spaces from $new_update
		$new_update = str_replace(' ', '', $new_update);
		// Splitting new update string with ,
		$parsed = explode(',', $new_update);
		
		// For each of the 2 comparisons
		for($i = 0; $i < count($parsed); $i++)
		{
			$sub_update = explode('=', $parsed[$i]);
	
			for($x = 0; $x < count($sub_update); $x++)
			{
				if(strpos($sub_update[$x], '\'') !== false)
				{
					continue;
				}
				
				// Each field needs prefix & postfix added
				$sub_field = explode('.', $sub_update[$x]);
								
				if(count($sub_field) == 2)
				{
					$sub_field[0] = $this->prefix . $sub_field[0] . $this->postfix;
				}
					
				$sub_update[$x] = implode('.', $sub_field);
			}
				
			$parsed[$i] = implode('=', $sub_update);			
		}
			
		$parsed = implode(', ', $parsed);
		
		
		// Replacing collected quotes back with DATA
		for($i = 0; $i < count($quote_base); $i++)
		{
			$parsed = str_replace('{DATA' . $i . '}', chr(39) . substr($quote_base[$i], 0, strlen($quote_base[$i]) - 1) . chr(39), $parsed);
		}
		
		// Returning parsed result
		return $parsed;
	}
}
?>
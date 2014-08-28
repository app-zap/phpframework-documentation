<?php
namespace AppZap\PHPFramework\Persistence;

use AppZap\PHPFramework\Configuration\Configuration;

/**
 * MySQL database wrapper class
 */
class MySQL {

  /**
   * @var array
   */
  public $trace = [];

  /**
   * @var \mysqli
   */
  protected $connection = NULL;

  /**
   * @var string|bool
   */
  protected $charset = FALSE;

  public function __destruct() {
    try {
      $this->disconnect();
    } catch (DBConnectionException $ex) {
    }
  }

  /**
   * Connects to the MySQL server, sets the charset for the connection and
   * selects the database
   *
   * @throws DBConnectionException when connection to database failed
   * @return resource Database connection handle
   */
  public function connect() {
    if (!($this->connection instanceof \mysqli)) {
      $db_configuration = Configuration::getSection('db');
      $this->connection = mysqli_connect($db_configuration['mysql.host'], $db_configuration['mysql.user'], $db_configuration['mysql.password'], $db_configuration['mysql.database']);
      // react on connection failures
      if (!$this->connection) {
        throw new DBConnectionException('Database connection failed');
      }
      if (isset($db_configuration['charset'])) {
        $this->set_charset($db_configuration['charset']);
      }
    }
    return $this->connection;
  }

  /**
   * Checks whether the connection to the database is established
   *
   * @return bool
   */
  public function is_connected() {
    return $this->get_connection() !== NULL;
  }

  /**
   * @return \mysqli
   */
  protected function get_connection() {
    return ($this->connection instanceof \mysqli) ? $this->connection : NULL;
  }

  /**
   * Disconnects previously opened database connection
   *
   * @throws DBConnectionException when connection was not opened
   */
  public function disconnect() {
    $connection = $this->get_connection();
    if ($connection === NULL) {
      throw new DBConnectionException('Tried to disconnect not opened connection.');
    }

    $disconnect = mysqli_close($connection);
    $this->connection = NULL;

    if (!$disconnect) {
      throw new DBConnectionException('Disconnecting database failed');
    }
  }

  /**
   * @param string $table
   * @return string
   */
  protected function prefix_table($table) {
    return Configuration::get('db', 'prefix', '') . $table;
  }

  /**
   * Sets the charset for transfer encoding
   *
   * @param string $charset Connection transfer charset
   */
  private function set_charset($charset = 'utf8') {

    // check if there is a assigned charset and compare it
    if ($this->charset == $charset) {
      return;
    }

    // set the new charset
    $sql = 'SET NAMES ' . $charset;
    $this->execute($sql, FALSE);

    // save the new charset to the globals
    $this->charset = $charset;
  }

  /**
   * Executes the passed SQL statement
   *
   * @param string $sql Finally escaped SQL statement
   * @return array Result data of the query
   */
  public function query($sql) {
    $result = $this->execute($sql);

    $retval = [];
    while ($row = $this->fetch($result)) {
      $retval[] = $row;
    }

    return $retval;
  }

  /**
   * Executes the passed SQL statement
   *
   * @param string $sql Finally escaped SQL statement
   * @return resource Result data of the query
   * @throws DBConnectionException
   * @throws DBQueryException
   */
  public function execute($sql) {
    if ($this->get_connection() === NULL) {
      throw new DBConnectionException('Database has to be connected before executing query.');
    }

    // execute the query
    $result = mysqli_query($this->get_connection(), $sql);

    if ($result === FALSE) {
      throw new DBQueryException('Database query failed. Error: "' . mysqli_error($this->connection) . '". Query was: "' . $sql . '"');
    }

    return $result;
  }

  /**
   * Returns number of affected rows of the last query
   *
   * @return int
   */
  public function affected() {
    return mysqli_affected_rows($this->get_connection());
  }

  /**
   * Returns the auto increment ID of the last query
   *
   * @return int
   */
  public function last_id() {
    return mysqli_insert_id($this->get_connection());
  }

  /**
   * Returns a row from the result set
   *
   * @param \mysqli_result $result Resultset from query-function
   * @return array
   */
  public function fetch($result) {
    return mysqli_fetch_assoc($result);
  }

  /**
   * Lists the fields of a table
   *
   * @param string $table Name of the table
   * @return array with field names
   */
  public function fields($table) {
    $sql = 'SHOW COLUMNS FROM ' . $this->prefix_table($table);
    $result = $this->query($sql);

    $output = [];
    foreach ($result as $row) {
      $output[] = $row['Field'];
    }

    return $output;
  }

  /**
   * Inserts dataset into the table and returns the auto increment key for it
   *
   * @param string $table Name of the table
   * @param string|array $input Dataset to insert into the table
   * @param boolean $ignore Use "INSERT IGNORE" for the query
   * @return int
   */
  public function insert($table, $input, $ignore = FALSE) {
    $ignore = ($ignore) ? ' IGNORE' : '';
    $this->execute('INSERT' . ($ignore) . ' INTO ' . $this->prefix_table($table) . ' SET ' . $this->values($input));
    return $this->last_id();
  }

  /**
   * Inserts dataset into the table or updates an existing key and returns the auto increment key for it
   *
   * @param string $table Name of the table
   * @param string|array $input Dataset to insert into the table
   * @param array $update_fields update this columns when ON DUPLICATE KEY
   * @return int
   */
  public function insert_or_update($table, $input, $update_fields = []) {
    $update_values = [];
    foreach ($update_fields as $fieldname) {
      if (isset($input[$fieldname])) {
        $update_values[$fieldname] = $input[$fieldname];
      }
    }

    $this->execute('INSERT INTO ' . $this->prefix_table($table) . ' SET ' . $this->values($input) . ' ON DUPLICATE KEY UPDATE ' . $this->values($update_values));
    return $this->last_id();
  }

  /**
   * Inserts a bunch of rows into the table
   *
   * @param string $table Name of the table
   * @param array $fields Array of field names
   * @param array $values Array of array of values sorted like the fields array
   */
  public function insert_all($table, $fields, $values) {
    $sql = 'INSERT INTO ' . $this->prefix_table($table) . ' (`' . implode('`, `', $fields) . '`) VALUES (';

    $rows = [];
    foreach ($values as $row) {
      $fields = [];
      foreach ($row as $field) {
        $fields[] = $this->escape($field);
      }
      $rows[] = implode('\', \'', $fields);
    }
    $sql .= implode('), (', $rows)
        . ')';

    $this->execute($sql);
  }

  /**
   * Replaces dataset in the table
   *
   * @param string $table Name of the table
   * @param string|array $input Dataset to replace in the table
   * @return resource
   */
  public function replace($table, $input) {
    return $this->execute('REPLACE INTO ' . $this->prefix_table($table) . ' SET ' . $this->values($input));
  }

  /**
   * Updates datasets in the table
   *
   * @param string $table Name of the table
   * @param string|array $input Dataset to write over the old one into the table
   * @param string|array $where Selector for the datasets to overwrite
   * @return resource
   */
  public function update($table, $input, $where) {
    return $this->execute('UPDATE ' . $this->prefix_table($table) . ' SET ' . $this->values($input) . ' WHERE ' . $this->where($where));
  }

  /**
   * Deletes datasets from table
   *
   * @param string $table Name of the table
   * @param string|array $where Selector for the datasets to delete
   */
  public function delete($table, $where = NULL) {
    $sql = 'DELETE FROM ' . $this->prefix_table($table);
    if ($where !== NULL) {
      $sql .= ' WHERE ' . $this->where($where);
    }
    $this->execute($sql);
  }

  /**
   * Selects datasets from table
   *
   * @param string $table Name of the table
   * @param string $select Fields to retrieve from table
   * @param string|array $where Selector for the datasets to select
   * @param string $order Already escaped content of order clause
   * @param int $start First index of dataset to retrieve
   * @param int $limit Number of entries to retrieve
   * @param boolean $fetch Return an pre-processed array of entries or the raw resource
   * @return array|resource
   */
  public function select($table, $select = '*', $where = NULL, $order = NULL, $start = NULL, $limit = NULL, $fetch = TRUE) {
    $sql = 'SELECT ' . $select . ' FROM ' . $this->prefix_table($table);

    if ($where !== NULL) $sql .= ' WHERE ' . $this->where($where);
    if ($order !== NULL) $sql .= ' ORDER BY ' . $order;
    if ($start !== NULL && $limit !== NULL) $sql .= ' LIMIT ' . $start . ',' . $limit;

    if ($fetch) {
      return $this->query($sql);
    }

    return $this->execute($sql);
  }

  /**
   * Select one row from table or false if there is no row
   *
   * @param string $table Name of the table
   * @param string $select Fields to retrieve from table
   * @param string|array $where Selector for the datasets to select
   * @param string $order Already escaped content of order clause
   * @return array|boolean
   */
  public function row($table, $select = '*', $where = NULL, $order = NULL) {
    $result = $this->select($table, $select, $where, $order, 0, 1, TRUE);
    return (count($result) > 0) ? $result[0] : FALSE;
  }

  /**
   * Select contents of one column from table
   *
   * @param string $table Name of the table
   * @param string $column Name of column to retrieve
   * @param string|array $where Selector for the datasets to select
   * @param string $order Already escaped content of order clause
   * @param int $start First index of dataset to retrieve
   * @param int $limit Number of entries to retrieve
   * @return array
   */
  public function column($table, $column, $where = NULL, $order = NULL, $start = NULL, $limit = NULL) {
    $result = $this->select($table, $column, $where, $order, $start, $limit, TRUE);

    $retval = [];
    foreach ($result as $row) {
      $retval[] = $row[$column];
    }
    return $retval;
  }

  /**
   * Select one field from table
   *
   * @param string $table Name of the table
   * @param string $field Name of the field to return
   * @param string|array $where Selector for the datasets to select
   * @param string $order Already escaped content of order clause
   * @internal param string $column Name of column to retrieve
   * @return mixed
   */
  public function field($table, $field, $where = NULL, $order = NULL) {
    $result = $this->row($table, $field, $where, $order);
    return $result[$field];
  }

  /**
   * Counts the rows matching the where clause in table
   *
   * @param string $table Name of the table
   * @param string|array $where Selector for the datasets to select
   * @return int
   */
  public function count($table, $where = NULL) {
    $result = $this->row($table, 'count(1)', $where);
    return ($result) ? $result['count(1)'] : 0;
  }

  /**
   * Selects the minmum of a column or false if there is no data
   *
   * @param string $table Name of the table
   * @param string $column Name of column to retrieve
   * @param string|array $where Selector for the datasets to select
   * @return int|boolean
   */
  public function min($table, $column, $where = NULL) {
    $result = $this->row($table, 'MIN(`' . $column . '`) as min', $where);
    return ($result) ? $result['min'] : FALSE;
  }

  /**
   * Selects the maximum of a column or false if there is no data
   *
   * @param string $table Name of the table
   * @param string $column Name of column to retrieve
   * @param string|array $where Selector for the datasets to select
   * @return int|boolean
   */
  public function max($table, $column, $where = NULL) {
    $result = $this->row($table, 'MAX(`' . $column . '`) as max', $where);
    return ($result) ? $result['max'] : FALSE;
  }

  /**
   * Selects the sum of a column
   *
   * @param string $table Name of the table
   * @param string $column Name of column to retrieve
   * @param string|array $where Selector for the datasets to select
   * @return int
   */
  public function sum($table, $column, $where = NULL) {
    $result = $this->row($table, 'SUM(`' . $column . '`) as sum', $where);
    return ($result) ? $result['sum'] : 0;
  }

  private function values($input) {
    if (!is_array($input)) {
      return $input;
    }

    $retval = [];
    foreach ($input as $key => $value) {
      if ($value === 'NOW()') {
        $retval[] = '`' . $key . '`' . ' = NOW()';
      } elseif ($value === NULL) {
        $retval[] = '`' . $key . '`' . ' = NULL';
      } else {
        $retval[] = '`' . $key . '`' . ' = \'' . $this->escape($value) . '\'';
      }
    }
    return implode(', ', $retval);

  }

  /**
   * Escape values
   *
   * @param mixed $value
   * @return string
   */
  public function escape($value) {
    $value = stripslashes($value);
    return mysqli_real_escape_string($this->get_connection(), (string)$value);
  }

  /**
   * Assembles a LIKE search for the WHERE clause
   *
   * @param string $search The string to search for (Will be pre- and appended with '%')
   * @param array $fields The fields to search in
   * @param string $mode One of 'OR' / 'AND'
   * @return string
   */
  public function search_clause($search, $fields, $mode = 'OR') {
    if (empty($search)) {
      throw new InputException('Empty search value not allowed. Use select instead.');
    }

    $arr = [];
    foreach ($fields as $f) {
      $arr[] = '`' . $f . '` LIKE \'%' . $search . '%\'';
    }
    return '(' . implode(' ' . trim($mode) . ' ', $arr) . ')';
  }

  private function where($array, $method = 'AND') {
    if (!is_array($array)) {
      return $array;
    }

    $output = [];
    foreach ($array AS $field => $value) {
      $operand = '=';
      $operand2 = 'IN';
      if (substr($field, -1) == '!') {
        $operand = '!=';
        $operand2 = 'NOT IN';
        $field = substr($field, 0, -1);
      } else if (substr($field, -1) == '?') {
        $operand = 'LIKE';
        $field = substr($field, 0, -1);
      }

      if (is_array($value)) {
        $arr = [];
        foreach ($value as $v) {
          $arr[] = $this->escape($v);
        }
        $output[] = '`' . $field . '`' . ' ' . $operand2 . ' (\'' . implode('\', \'', $arr) . '\')';
      } else {
        $output[] = '`' . $field . '`' . ' ' . $operand . ' \'' . $this->escape($value) . '\'';
      }
    }
    return implode(' ' . $method . ' ', $output);
  }

}

class DBConnectionException extends \Exception {
}

class DBQueryException extends \Exception {
}

class DBDatabaseException extends \Exception {
}

class InputException extends \Exception {
}

class DBConfigException extends \Exception {
}

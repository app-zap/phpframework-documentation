<?php
namespace AppZap\PHPFramework\Persistence;

use AppZap\PHPFramework\StaticConfiguration as Configuration;

/**
 * MySQL database wrapper class
 */
class MySQL {

  /**
   * @var array
   */
  public $trace = array();

  /**
   * @var \mysqli
   */
  protected $connection = NULL;

  /**
   * @var string|bool
   */
  protected $charset = FALSE;

  /**
   * @var array
   */
  protected $config = array();

  /**
   * @param \IConfigReader $config Config object containing the database config
   * @param string $connection_target Name of the database connection to read the settings from
   */
  public function __construct($config = NULL, $connection_target = 'default') {
    if (is_null($config)) {
      $config = Configuration::getConfigurationObject();
    }
    $this->config = array(
        'host' => $config->get('db.mysql.' . $connection_target . '.host')
    , 'user' => $config->get('db.mysql.' . $connection_target . '.user')
    , 'pass' => $config->get('db.mysql.' . $connection_target . '.password')
    , 'name' => $config->get('db.mysql.' . $connection_target . '.database')
    , 'char' => $config->get('db.mysql.' . $connection_target . '.charset', 'utf8')
    , 'prefix' => $config->get('db.mysql.' . $connection_target . '.tableprefix', '')
    );

    if ($this->config['name'] === NULL) {
      throw new DBConfigException('Please define the connection parameters for "' . $connection_target . '"');
    }
  }

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

    $connection = $this->connection();
    if ($connection !== NULL) {
      return $connection;
    }

    // don't connect again if it's already done
    $connection = mysqli_connect($this->config['host'], $this->config['user'], $this->config['pass'], $this->config['name']);

    // react on connection failures
    if (!$connection) {
      throw new DBConnectionException('Database connection failed');
    }

    $this->connection = $connection;

    $this->set_charset($this->config['char']);

    return $connection;
  }

  /**
   * Checks whether the connection to the database is established
   *
   * @return bool
   */
  public function is_connected() {
    return $this->connection() !== NULL;
  }

  /**
   * @return \mysqli
   */
  protected function connection() {
    return ($this->connection instanceof \mysqli) ? $this->connection : NULL;
  }

  /**
   * Disconnects previously opened database connection
   *
   * @throws DBConnectionException when connection was not opened
   */
  public function disconnect() {
    $connection = $this->connection();
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
   * @param string $table_prefix Prefix to use for all future statements
   */
  public function set_global_table_prefix($table_prefix) {
    $this->config['prefix'] = $table_prefix;
  }

  /**
   * @return string
   */
  public function get_global_table_prefix() {
    return $this->config['prefix'];
  }

  private function prefix_table($table) {
    return $this->config['prefix'] . $table;
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

    $retval = array();
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
   */
  public function execute($sql) {
    if ($this->connection() === NULL) {
      throw new DBConnectionException('Database has to be connected before executing query.');
    }

    // execute the query
    $result = mysqli_query($this->connection(), $sql);

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
    return mysqli_affected_rows($this->connection());
  }

  /**
   * Returns the auto increment ID of the last query
   *
   * @return int
   */
  public function last_id() {
    return mysqli_insert_id($this->connection());
  }

  /**
   * Returns a row from the result set
   *
   * @param resource $result Resultset from query-function
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

    $output = array();
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
  public function insert_or_update($table, $input, $update_fields = array()) {
    $update_values = array();
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

    $rows = array();
    foreach ($values as $row) {
      $fields = array();
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

    $retval = array();
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

    $retval = array();
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
   */
  public function escape($value) {
    $value = stripslashes($value);
    return mysqli_real_escape_string($this->connection(), (string)$value);
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

    $arr = array();
    foreach ($fields as $f) {
      $arr[] = '`' . $f . '` LIKE \'%' . $search . '%\'';
    }
    return '(' . implode(' ' . trim($mode) . ' ', $arr) . ')';
  }

  private function where($array, $method = 'AND') {
    if (!is_array($array)) {
      return $array;
    }

    $output = array();
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
        $arr = array();
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

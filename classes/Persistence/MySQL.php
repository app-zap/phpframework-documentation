<?php
namespace AppZap\PHPFramework\Persistence;

use AppZap\PHPFramework\Configuration\Configuration;

/**
 * MySQL database wrapper class
 */
class MySQL {

  /**
   * @var \mysqli
   */
  protected $connection = NULL;

  /**
   * @var string
   */
  protected $charset;

  /**
   * Connects to the MySQL server, sets the charset for the connection and
   * selects the database
   *
   * @throws DBConnectionException when connection to database failed
   */
  public function connect() {
    if (!($this->connection instanceof \mysqli)) {
      $db_configuration = Configuration::getSection('db');
      $this->connection = mysqli_connect($db_configuration['mysql.host'], $db_configuration['mysql.user'], $db_configuration['mysql.password'], $db_configuration['mysql.database']);
      if (!$this->connection) {
        throw new DBConnectionException('Database connection failed');
      }
      if (isset($db_configuration['charset'])) {
        $this->set_charset($db_configuration['charset']);
      }
    }
  }

  /**
   * Checks whether the connection to the database is established
   *
   * @return bool
   */
  public function is_connected() {
    return (bool) $this->connection;
  }

  /**
   * Sets the charset for transfer encoding
   *
   * @param string $charset Connection transfer charset
   */
  protected function set_charset($charset) {
    if ($this->charset !== $charset) {
      $this->charset = $charset;
      $sql = 'SET NAMES ' . $this->charset;
      $this->execute($sql, FALSE);
      return;
    }
  }

  /**
   * Executes the passed SQL statement
   *
   * @param string $sql Finally escaped SQL statement
   * @return array Result data of the query
   */
  public function query($sql) {
    $result = $this->execute($sql);
    $rows = [];
    while ($row = $this->fetch($result)) {
      $rows[] = $row;
    }
    return $rows;
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
    $this->connect();
    // execute the query
    $result = mysqli_query($this->connection, $sql);
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
    $this->connect();
    return mysqli_affected_rows($this->connection);
  }

  /**
   * Returns the auto increment ID of the last query
   *
   * @return int
   */
  public function last_id() {
    $this->connect();
    return mysqli_insert_id($this->connection);
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
    $sql = 'SHOW COLUMNS FROM ' . $table;
    $result = $this->query($sql);

    $fields = [];
    foreach ($result as $row) {
      $fields[] = $row['Field'];
    }

    return $fields;
  }

  /**
   * Inserts dataset into the table and returns the auto increment key for it
   *
   * @param string $table Name of the table
   * @param array $input Dataset to insert into the table
   * @param boolean $ignore Use "INSERT IGNORE" for the query
   * @return int
   */
  public function insert($table, $input, $ignore = FALSE) {
    $ignore = $ignore ? ' IGNORE' : '';
    $this->execute('INSERT' . $ignore . ' INTO ' . $table . ' SET ' . $this->values($input));
    return $this->last_id();
  }

  /**
   * Inserts dataset into the table or updates an existing key and returns the auto increment key for it
   *
   * @param string $table Name of the table
   * @param array $input Dataset to insert into the table
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

    $this->execute('INSERT INTO ' . $table . ' SET ' . $this->values($input) . ' ON DUPLICATE KEY UPDATE ' . $this->values($update_values));
    return $this->last_id();
  }

  /**
   * Replaces dataset in the table
   *
   * @param string $table Name of the table
   * @param array $input Dataset to replace in the table
   * @return resource
   */
  public function replace($table, $input) {
    return $this->execute('REPLACE INTO ' . $table . ' SET ' . $this->values($input));
  }

  /**
   * Updates datasets in the table
   *
   * @param string $table Name of the table
   * @param array $input Dataset to write over the old one into the table
   * @param array $where Selector for the datasets to overwrite
   * @return resource
   */
  public function update($table, $input, $where) {
    return $this->execute('UPDATE ' . $table . ' SET ' . $this->values($input) . ' WHERE ' . $this->where($where));
  }

  /**
   * Deletes datasets from table
   *
   * @param string $table Name of the table
   * @param array $where Selector for the datasets to delete
   */
  public function delete($table, $where = NULL) {
    $sql = 'DELETE FROM ' . $table;
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
   * @param  array $where Selector for the datasets to select
   * @param string $order Already escaped content of order clause
   * @param int $start First index of dataset to retrieve
   * @param int $limit Number of entries to retrieve
   * @param boolean $fetch Return an pre-processed array of entries or the raw resource
   * @return array|resource
   */
  public function select($table, $select = '*', $where = NULL, $order = NULL, $start = NULL, $limit = NULL, $fetch = TRUE) {
    $sql = 'SELECT ' . $select . ' FROM ' . $table;

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
   * @param array $where Selector for the datasets to select
   * @param string $order Already escaped content of order clause
   * @return array|boolean
   */
  public function row($table, $select = '*', $where = NULL, $order = NULL) {
    $result = $this->select($table, $select, $where, $order, 0, 1, TRUE);
    return (count($result) > 0) ? $result[0] : FALSE;
  }

  /**
   * Select one field from table
   *
   * @param string $table Name of the table
   * @param string $field Name of the field to return
   * @param array $where Selector for the datasets to select
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
   * @param array $where Selector for the datasets to select
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
   * @param array $where Selector for the datasets to select
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

  /**
   * @param array $input
   * @return string
   */
  protected function values($input) {
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
    $this->connect();
    $value = stripslashes($value);
    return mysqli_real_escape_string($this->connection, (string)$value);
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

  /**
   * @param array $array
   * @param string $method
   * @return string
   */
  protected function where($array, $method = 'AND') {
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

<?php
namespace AppZap\PHPFramework\Persistence;

use AppZap\PHPFramework\Configuration\Configuration;

/**
 * Database wrapper class
 */
class DatabaseConnection {

  const VALUE_NOW = 100;

  /**
   * @var \PDO
   */
  protected $connection = NULL;

  /**
   * @var string
   */
  protected $charset;

  /**
   * Connects to the MySQL server, sets the charset for the connection and
   * selects the database
   */
  public function connect() {
    if (!($this->connection instanceof \PDO)) {
      $db_configuration = Configuration::getSection('db');
      $this->connection = new \PDO('mysql:host=' . $db_configuration['mysql.host'] . ';dbname=' . $db_configuration['mysql.database'], $db_configuration['mysql.user'], $db_configuration['mysql.password']);
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
    $this->charset = $charset;
    $sql = 'SET NAMES ' . $this->charset;
    $this->execute($sql, FALSE);
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
    foreach ($result as $row) {
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
    try {
      $result = $this->connection->query($sql);
    } catch(\PDOException $e) {
      // under HHVM we get a \PDOException instead of FALSE if the query fails
      $result = FALSE;
    }
    if ($result === FALSE) {
      throw new DBQueryException('Database query failed. Error: "' . print_r($this->connection->errorInfo(), 1) . '". Query was: "' . $sql . '"');
    }
    return $result;
  }

  /**
   * Returns the auto increment ID of the last query
   *
   * @return int
   */
  public function last_id() {
    $this->connect();
    return $this->connection->lastInsertId();
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
    if (count($input)) {
      $values = ' SET ' . $this->values($input);
    } else {
      $values = '(id) VALUES (NULL)';
    }
    $this->execute('INSERT' . $ignore . ' INTO ' . $table . $values);
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
   * @return array
   */
  public function select($table, $select = '*', $where = NULL, $order = NULL, $start = NULL, $limit = NULL) {
    $sql = 'SELECT ' . $select . ' FROM ' . $table;

    if ($where !== NULL) $sql .= ' WHERE ' . $this->where($where);
    if ($order !== NULL) $sql .= ' ORDER BY ' . $order;
    if ($start !== NULL && $limit !== NULL) $sql .= ' LIMIT ' . $start . ',' . $limit;

    return $this->query($sql);
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
    $result = $this->select($table, $select, $where, $order, 0, 1);
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
      if ($value === self::VALUE_NOW) {
        $retval[] = '`' . $key . '`' . ' = NOW()';
      } elseif ($value === NULL) {
        $retval[] = '`' . $key . '`' . ' = NULL';
      } else {
        $retval[] = '`' . $key . '`' . ' = ' . $this->escape($value);
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
    return $this->connection->quote((string)$value);
  }

  /**
   * @param array $where
   * @param string $method
   * @return string
   * @throws InputException
   */
  protected function where($where, $method = 'AND') {
    if (!is_array($where)) {
      throw new InputException('where clause has to be an associative array', 1409767864);
    }

    $output = [];
    foreach ($where AS $field => $value) {
      $operand = '=';
      $operand2 = 'IN';
      if (substr($field, -1) === '!') {
        $operand = '!=';
        $operand2 = 'NOT IN';
        $field = substr($field, 0, -1);
      } else if (substr($field, -1) == '?') {
        $operand = 'LIKE';
        $field = substr($field, 0, -1);
      }

      if (is_array($value)) {
        $value = array_map([$this, 'escape'], $value);
        $output[] = '`' . $field . '`' . ' ' . $operand2 . ' (' . implode(', ', $value) . ')';
      } else {
        $output[] = '`' . $field . '`' . ' ' . $operand . ' ' . $this->escape($value);
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

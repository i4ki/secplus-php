<?php

namespace SecPlus;

/**
 * Interface to Models
 */
interface IModel {
  
}

/**
 * Every Model need extend this abstract class
 */
abstract class AbstractModel implements IModel {
  /**
   * Singleton instance of the database connection
   */
  public static $conn = null;
  protected $config;
  protected $_table_name;
  protected $_id_name = 'id';
  protected $_vo_name;

  public function __construct() {
    $this->_connect();
    $this->config = \Config::getInstance();
  }
  
  public function _connect() {
    self::$conn = Database::getConnection();
  }

  public function setTableName($tname) {
    $this->_table_name = $tname;
  }

  public function getTableName() {
    return $this->_table_name;
  }

  public function setValueObjectName($name) {
    $this->_vo_name = $name;
  }

  public function _setupDAO() {
    $this->_table_name = !empty($this->_table_name) ? $this->_table_name : strtolower(str_replace(__NAMESPACE__, "", str_replace('DAO', '', get_class($this))));
    $this->_vo_name = !empty($this->_vo_name) ? $this->_vo_name : ucfirst($this->_table_name);
  }

  public function get($id) {
    $this->_setupDAO();

    try {
      $stmt = self::$conn->prepare("SELECT * FROM " . $this->_table_name . " WHERE " . $this->_id_name . " = :id");
      $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
      $stmt->execute();

      $result = $stmt->fetchAll();

      if (count($result) > 0) {
        $r = $result[0];
        $voName = $this->_vo_name;
        $obj = new $voName();
        $obj = $this->map2object($obj, $r);

        return $obj;
      }

      return NULL;
    } catch (Exception $e) {
      if ($this->config->isDebug()) {
        print $e->getMessage();
        die();
      } else {
        print "database error.\n";
        die();
      }
    }

    return NULL;
  }

  public function delete($id) {
    $this->_setupDAO();

    try {
      $stmt = self::$conn->prepare("DELETE FROM " . $this->_table_name . " WHERE " . $this->_id_name . " = :id");
      $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
      $stmt->execute();

      return $stmt->rowCount() > 0;
    } catch (Exception $e) {
      if ($this->config->isDebug()) {
        print $e->getMessage();
        die();
      } else {
        print "database error.\n";
        die();
      }
    }

    return NULL;
  }

  public function first() {
    $this->_setupDAO();

    try {
      $stmt = self::$conn->prepare("select * from " . $this->_table_name . " limit 1");
      $stmt->execute();

      $result = $stmt->fetchAll();
      $objects = array();

      if (count($result) == 1) {
        $voName = $this->_vo_name;
        $obj = new $voName();
        $obj = $this->map2object($obj, $result[0]);
        return $obj;
      } else {
        return NULL;
      }      
    } catch (Exception $e) {
      if ($this->config->isDebug()) {
        print $e->getMessage();
      }
    }
  }

  public function getAll() {
    $this->_setupDAO();
    
    try {
      $stmt = self::$conn->prepare("select * from " . $this->_table_name);
      $stmt->execute();

      $result = $stmt->fetchAll();
      $objects = array();

      for ($i = 0; $i < count($result); $i++) {
        $r = $result[$i];
        $voName = $this->_vo_name;
        $obj = new $voName();
        $obj = $this->map2object($obj, $r);
        $objects[] = $obj;
      }

      return $objects;
    } catch (Exception $e) {
      if ($this->config->isDebug()) {
        print $e->getMessage();
        die();
      }
    }

    return NULL;
  }

  public function update($obj) {

    $this->_setupDAO();

    try {
      $data = $obj->getData();
      $keys = array_keys($data);

      $sql = SQLBuilder::update($this->_table_name, $keys, array($this->_id_name));
      $stmt = self::$conn->prepare($sql);

      foreach($data as $name => &$val) {
        if ($name == $this->_id_name) {
          continue;
        }

        $stmt->bindParam(':' . $name, $val[0], $val[1]); 
      }
      
      $stmt->bindParam(':' . $this->_id_name, $data[$this->_id_name][0], \PDO::PARAM_INT);

      $stmt->execute();

      return $stmt->rowCount() > 0;
    } catch (Exception $e) {
      if ($this->config->isDebug()) {
        print $e->getMessage();
        die();
      }
    }

    return 0;
  }

  public function save($obj) {
    $this->_setupDAO();

    try {
      $data = $obj->getData();
      $valid_keys = $this->getColumns();
      $keys = array_keys($data);

      if (!$this->_validate_keys($keys, $valid_keys)) {
        throw new \Exception("Invalid field name in object: " . implode(", ", $keys));
      }
      
      $sql = SQLBuilder::insert($this->_table_name, $keys, $this->_id_name);
      $stmt = self::$conn->prepare($sql);

      foreach($data as $name => &$val) {
        if ($name == $this->_id_name) {
          continue;
        }

        $stmt->bindParam(':' . $name, $val[0], $val[1]);
      }
      
      $stmt->execute();

      return $stmt->rowCount() > 0;
    } catch (Exception $e) {
      if ($this->config->isDebug()) {
        print $e->getMessage();
        print "query: " . $sql;
        die();
      }
    }

    return 0;
  }

  protected function _validate_keys($keys, $valid_keys) {
    foreach ($keys as $k) {
      if (!in_array($k, $valid_keys)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  public function map2object($user, $res) {
    $keys = array_keys($res);
    for ($j = 0; $j < count($keys); $j++) {
      if (is_string($keys[$j])) {
        $user->{$keys[$j]} = $res[$keys[$j]];
      }
    }

    return $user;
  }

  public function map2array($res) {
    $ar = array();
    $keys = array_keys($res);
    for ($j = 0; $j < count($keys); $j++) {
      if (is_string($keys[$j])) {
        $ar[] = array($keys[$j], $res[$keys[$j]]);
      }
    }

    return $ar;
  }

  public function getDataByColumn($columnName, $value, $type = \PDO::PARAM_STR) {
    $this->_setupDAO();
    
    try {
      $stmt = self::$conn->prepare("select * from " . $this->_table_name . " where " . $columnName . " = :".$columnName);
      $stmt->bindParam(':' . $columnName, $value, $type);
      $stmt->execute();

      $result = $stmt->fetchAll();
      $objects = array();

      for ($i = 0; $i < count($result); $i++) {
        $r = $result[$i];
        $voName = $this->_vo_name;
        $obj = new $voName();
        $obj = $this->map2object($obj, $r);
        $objects[] = $obj;
      }

      return $objects;
    } catch (Exception $e) {
      if ($this->config->isDebug()) {
        print $e->getMessage();
        die();
      }
    }

    return NULL;    
  }

public function searchInColumn($columnName, $value, $type = \PDO::PARAM_STR) {
    $this->_setupDAO();
    
    try {
      $stmt = self::$conn->prepare("select * from " . $this->_table_name . " where " . $columnName . " like :".$columnName);
			$value = "%".$value."%";
      $stmt->bindParam(':' . $columnName, $value, $type);

      $stmt->execute();

      $result = $stmt->fetchAll();
      $objects = array();

      for ($i = 0; $i < count($result); $i++) {
        $r = $result[$i];
        $voName = $this->_vo_name;
        $obj = new $voName();
        $obj = $this->map2object($obj, $r);
        $objects[] = $obj;
      }

      return $objects;
    } catch (Exception $e) {
      if ($this->config->isDebug()) {
        print $e->getMessage();
        die();
      }
    }

    return NULL;    
  }


  public function getColumns() {
    $this->_setupDAO();
    try {
      $sql = "DESCRIBE ". $this->_table_name;
      $stmt = self::$conn->prepare($sql);
      $stmt->execute();
      $table_fields = $stmt->fetchAll(\PDO::FETCH_COLUMN);

      return $table_fields;
    } catch (Exception $e) {
      if ($this->config->isDebug()) {
        print $e->getMessage();
        print "query: " . $sql;
      }
    }
    return NULL;
  }

  public function __call($func, $args) {
    if (preg_match('/^get/', $func)) {
      $prop = lcfirst(substr($func, 3));
      if (empty($prop)) {
        Helper::throwPermissionDeniedMethod($func);
        return;
      }

      if (!in_array($prop, $this->getColumns())) {
        Helper::throwPermissionDeniedMethod($prop);
        return;
      }
      
      if (count($args) == 1) {
        return $this->getDataByColumn($prop, $args[0]);
      } else if (count($args) == 2) {
        return $this->getDataByColumn($prop, $args[0], $args[1]);
      }
    }
  }
}

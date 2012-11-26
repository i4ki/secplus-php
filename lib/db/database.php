<?php

namespace SecPlus;

/**
 * Manages the database
 */
class Database {
  private static $conn = null;

  private function __construct() {
    /**
     * *Never* try to instantiate this class
     */
  }

  /**
   * Connect to database
   */
  public static function getConnection() {
    $config = Config::getInstance();
    try {
      $dbms = $config->getDbms();
      $host = $config->getDbHost();
      $user = $config->getDbUser();
      $pass = $config->getDbPass();
      $dbname = $config->getDbDatabase();
	
      $datasource = $dbms . ":" . "host=" . $host . ";dbname=" . $dbname;
      
      self::$conn = new \PDO($datasource, $user, $pass);

      // Sets ATTR_EMULATE_PREPARES to false to prevent SQL injection attacks
      self::$conn->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false); 

      // Sets Errorhandling to Exception
      self::$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION); 

      return self::$conn;

    } catch (PDOException $e) {
      if ($config->isDebug()) {
        print "Database connection error.";
      } else {
        print "Database connection error: " . $e->getMessage();
      }
      die();
    } catch (Exception $e) {
      print "error: " . $e->getMessage();
      die();
    }    
  }

  public static function closeConnection() {
    self::$conn = null;
  }
}

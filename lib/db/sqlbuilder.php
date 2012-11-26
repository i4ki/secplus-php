<?php

namespace SecPlus;

class SQLBuilder {
  public static function update($table_name, $values, $where = NULL) {
    $sql = "UPDATE " . $table_name . " SET ";
    if (count($values) < 1) {
      return NULL;
    }

    $l = count($values);
    for ($i = 0; $i < $l; $i++) {
      if(in_array($values[$i], $where)){
        continue;
      }
      $sql .= $values[$i] . " = :" . $values[$i];
      if ($i < ($l - 1)) {
        $sql .= ", ";
      }
    }

    if (!empty($where)) {
      $sql .= " WHERE ";
      $l = count($where);
      for ($i = 0; $i < $l; $i++) {
        $sql .= $where[$i] . " = :" . $where[$i];
        if ($i < ($l - 1)) {
          $sql .= " AND ";
        }
      }
    }

    return $sql;
  }

  public static function insert($table_name, $values, $primary_key = 'id') {
    $sql = "INSERT INTO " . $table_name;
    if (count($values) < 1) {
      return NULL;
    }

    $l = count($values);
    if ($l < 1) {
      return NULL;
    }

    $sql .= " (";
    
    for ($i = 0; $i < $l; $i++) {
      if ($values[$i] == $primary_key) {
        continue;
      }
      $sql .= $values[$i];
      if ($i < ($l - 1)) {
        $sql .= ", ";
      }
    }

    $sql .= ") VALUES (";

    for ($i = 0; $i < $l; $i++) {
      if ($values[$i] == $primary_key) {
        continue;
      }
      
      $sql .= ':' . $values[$i];
      if ($i < ($l - 1)) {
        $sql .= ", ";
      }
    }

    $sql .= ')';

    return $sql;
  }
}

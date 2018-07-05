<?php
class MysqlCache{
    public static $db = null;
    public static $tableName = 'oneindex_cache';
    public static function init( $connetInfo = NULL ){
        if( !$connetInfo && defined( 'DATABASE_URL' ) && DATABASE_URL ){
            $connetInfo =  parse_url(DATABASE_URL);
            $connetInfo['db'] = substr($connetInfo["path"], 1);
        }
        if( $connetInfo && self::$db === null ){
            self::$db = new PDO("mysql:dbname=${connetInfo['db']};host=${connetInfo['host']}", $connetInfo['user'], $connetInfo['pass']);
        }
    }
    public static function set( $key , $value , $pre = null ){
        self::init();
        $nkey = $pre.$key;
        if( !is_array( $value ) ){
            $value = [
                '__Mysql_Cache_Auto_Type' => 'string',
                'value' => $value
                
            ];
        }
        $nvalue = json_encode( $value );
        
        $queryString = 'REPLACE INTO ' . self::$tableName . ' VALUES (:key, :value);';
        $mysql = self::$db->prepare($queryString);
        $mysql->bindParam(':key', $nkey , PDO::PARAM_STR);
        $mysql->bindParam(':value', $nvalue , PDO::PARAM_STR);
        $res = $mysql->execute();
        if( $mysql->errorCode() && $mysql->errorCode() == '42S02' ){
            $sql = 'CREATE TABLE IF NOT EXISTS `' . self::$tableName . '`';
            $sql.= '(`key` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,`value` text COLLATE utf8mb4_unicode_ci NOT NULL , UNIQUE KEY `key` (`key`) );';
            self::$db->exec( $sql );
            return self::set( $key , $value , $pre );
        }
        return $res;
        
    }
    public static function get( $key , $pre = null ){
        self::init();
        $nkey = $pre.$key;
      
        $mysql = self::$db->prepare(
            'SELECT `value` FROM `' . self::$tableName . '` WHERE `key` = :key;'
        );
        $mysql->bindParam(':key', $nkey, PDO::PARAM_STR);
        $mysql->execute();
        $value = false;
        if ($row = $mysql->fetch(PDO::FETCH_OBJ)) {
            $value = json_decode($row->value , true);
        }
        if( 
            is_array($value) && 
            count($value) == 2 && 
            isset( $value['__Mysql_Cache_Auto_Type'] ) && 
            $value['__Mysql_Cache_Auto_Type'] == 'string' && 
            isset( $value['value'] ) &&
            !is_array( $value['value'] ) 
        ){
            $value = $value['value'];  
        }
        return $value;
    }
    public static function deleteAll( $pre = null ){
        self::init();
        if( $pre ){
            $pre .= '%';
            $mysql = self::$db->prepare(
                ' DELETE FROM `'.self::$tableName.'` WHERE `key` like :key;'
            );
            $mysql->bindParam(':key', $pre, PDO::PARAM_STR);
            $mysql->execute();
        }else{
            self::$db->exec( " TRUNCATE `". self::$tableName ."` " );
        }
    }
}
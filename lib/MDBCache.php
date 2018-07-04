<?php
class MDBCache{
    public static $mconnet = null;
    public static $mdb = null;
    public static $mCollection = 'cache';
    public static function init( $connetInfo = NULL ){
        if( !$connetInfo && defined( 'MONGODB_URI' ) && MONGODB_URI ){
            $connetInfo = MONGODB_URI;
        }
        if( $connetInfo && self::$mconnet === null ){
            self::$mconnet = new mongoClient( $connetInfo );
            $tmp = explode('/' , $connetInfo ) ;
            $mdbCollection = end( $tmp );
            self::$mconnet = self::$mconnet->selectDB( $mdbCollection );
            
            $collectionName = self::$mCollection;
            self::$mdb = self::$mconnet->$collectionName;
        }
    }
    public static function set( $key , $value , $pre = null ){
        self::init();
        $res = self::$mdb->findOne(['_id' => $pre.$key ]);
        if( $res ){
            self::$mdb->save( 
                ['_id' => $pre.$key,
                'value' => json_encode($value) ] );
        }else{
            return self::$mdb->insert( [
                '_id' => $pre.$key,
                'value' => json_encode($value)
            ] );
        }
        
    }
    public static function get( $key , $pre = null ){
        self::init();
        $res = self::$mdb->findOne(['_id' => $pre.$key ]);
        if( $res && isset($res['value']) ){
            return json_decode($res['value'],1);
        }
    }
    public static function deleteAll( $pre = null ){
        self::init();
        if( !$pre ){
            return self::$mdb->remove([]);
        }else{
            return self::$mdb->remove( [
                '_id' => new MongoRegex("/^$pre/i")
            ] );
            
        }
    }
}

<?php
/***
 * Author: Mehmet Hazar Artuner
 * Contact: hazar.artuner@gmail.com
 *
 * Sample Usage:
 *
 * The methods within the __construct method have to execute with same order

    class UserObject extends AbstractObject{
        function __construct($data = null){
            global $DB;

            $this->_setTableName("table_name");
            $this->_setMap(array(
                        "user_id",
                        "username",
                        "email",
                        "password"
                    ));

            $this->_setPrimaryKey("user_id");

            parent::__construct($DB, $data);
        }
    }
 *
 *
 */

namespace com\admin\system\objects;

use com\admin\system\utils\PixelException;


abstract class AbstractObject{
    private $_properties = array();
    private $_map = array();
    private $_table = null;
    private $_primaryKey = null;
    private $_DB;

    /***
     * @param \DB $DB
     * @param array $data associated array olmalı
     * Bu method çalıştırılmadan önce _setMap() metodu mutlaka çalıştırılmalı
     */
    function __construct(\DB $DB, $data = null){
        if(is_object($data) || is_array($data)){
            foreach ($data as $key=>$val) {
                $this->{$key} = $val;
            }
        }

        $this->_DB = $DB;
    }

    public function __get($key){
        if(in_array($key, $this->_map)){
            return $this->_properties[$key];
        }
        else{
            return false;
        }
    }

    public function __set($key, $val){
        if(in_array($key, $this->_map)){
            $this->_properties[$key] = $val;
            return true;
        }
        else{
            return false;
        }
    }

    public function isMapped($propertyName){
        if(in_array($propertyName, $this->_map)){
            return true;
        }
        else{
            return false;
        }
    }

    /**
     * @param array $map
     * @return bool
     */
    protected function _setMap($map){
        if(is_array($map) || is_object($map)){
            $this->_map = array();

            foreach($map as $key){
                if(!empty($key))
                $this->_map[] = $key;
            }

            return true;
        }
        else{
            return false;
        }
    }

    /**
     * @return array
     */
    public function getMap(){
        return $this->_map;
    }

    /**
     * @return string
     */
    function getTable(){
        return $this->_table;
    }

    /**
     * @return array
     */
    public function toArray(){
        $data = array();

        foreach($this->_properties as $key=>$val){
            $data[$key] = $val;
        }

        return $data;
    }

    /**
     * @param string $tableName
     * @return bool
     */
    protected function _setTableName($table){
        if(gettype($table) === "string"){
            $this->_table = $table;
            return true;
        }
        else{
            return false;
        }
    }

    /**
     * @return string|null
     */
    protected function _getTableName(){
        return $this->_table;
    }

    /***
     * @param $primaryKey
     * @return bool
     * Bu method çalıştırılmadan önce _setMap() methodu mutlaka çalıştırılmadı.
     */
    protected function _setPrimaryKey($primaryKey){
        if((gettype($primaryKey) === "string") && in_array($primaryKey, $this->_map)){
            $this->_primaryKey = $primaryKey;
            return true;
        }
        else{
            return false;
        }
    }

    /**
     * @return string|null
     */
    protected function _getPrimaryKey(){
        return $this->_primaryKey;
    }

    /**
     * @param $value
     * @return bool
     */
    protected function _setPrimaryValue($value){
        if($this->_primaryKey !== null){
            $this->_properties[$this->_primaryKey] = $value;
            return true;
        }
        else{
            return false;
        }
    }

    /**
     * @return string|null
     */
    protected function _getPrimaryValue(){
        if($this->_primaryKey !== null){
            return $this->_properties[$this->_primaryKey];
        }
        else{
            return null;
        }
    }

    /**
     * @return bool
     * @throws PixelException
     */
    protected function _checkIfAvailableForDbAction(){
        if($this->$_primaryKey === null){
            throw new PixelException("Hata: primaryKey değeri atanmamış");
        }
        else if($this->_table === null){
            throw new PixelException("Hata: tablo ismi atanmamış!");
        }
        else if($this->_properties === null){
            throw new PixelException("Hata: mapping işlemi yapılmamış. setMap() methodunu çalıştırın!");
        }
        else{
            return true;
        }
    }

    /**
     * @return bool
     * @throws PixelException
     */
    public function save(){
        $this->_checkIfAvailableForDbAction();
        $primaryValue = $this->_getPrimaryValue();

        if(empty($primaryValue)){
            return $this->insert();
        }
        else{
            return $this->update();
        }
    }

    /**
     * @return bool
     * @throws PixelException
     */
    public function insert(){
        $this->_checkIfAvailableForDbAction();

        if($user_id = $this->_DB->insert($this->_table, $this->toArray())){
            $this->_setPrimaryValue($user_id);
            return $user_id;
        }
        else{
            return false;
        }
    }

    /**
     * @return bool
     * @throws PixelException
     */
    public function update(){
        $this->_checkIfAvailableForDbAction();

        $primaryValue = $this->_getPrimaryValue();

        if(empty($primaryValue)){
            throw new PixelException("PrimaryKey değeri boş olmamalı!");
        }
        else if($this->_DB->update($this->_table, $this->toArray(), array($this->_primaryKey=>$this->_properties[$this->_getPrimaryKey()]))){
            return $primaryValue;
        }
        else{
            return false;
        }
    }

    /**
     * @return bool
     * @throws PixelException
     */
    public function delete(){
        $this->_checkIfAvailableForDbAction();

        $primaryValue = $this->_getPrimaryValue();

        if(!empty($primaryValue)){
            return $this->_DB->execute("DELETE FROM {$this->_table} WHERE {$this->_primaryKey}=?", array($primaryValue));
        }

        return true;
    }

    /**
     * @param array $data
     * @return static[]
     */
    public static function convertToObjectCollection($data = array()){
        $collection = array();

        if(is_array($data) && sizeof($data) > 0){
            foreach($data as $d){
                $collection[] = new static($d);
            }
        }

        return $collection;
    }
}
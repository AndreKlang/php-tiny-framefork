<?php

namespace KlangTiny\App\Model\Db;
use KlangTiny\App;
use KlangTiny\App\Model;

abstract class Mysql extends Model {

    const TYPE_INT = 1;
    const TYPE_FLOAT = 2;
    const TYPE_STRING = 3;
    const TYPE_BOOLEAN = 4;

    /**
     * If this is true, save() will create a new record
     * If this is false, save() will use getId() to update a record
     * @var bool
     */
    protected $_isRecordNew = true;

    // overwrite in each model as needed
    protected $_table_name = null;
    protected $_id_col = 'entity_id';
    protected $_schema = array(
        "entity_id" => self::TYPE_INT
    );

    /** @var string[] */
    protected $_data = null;

    public function load($id){

        // get data from DB
        $data = (array) App::getMysql()
            ->table($this->getTableName())
            ->find($id,$this->getIdCol());

        // make sure that we know this is loaded
        if(!empty($data)) $this->_isRecordNew = false;

        // save data in object instance
        $this->_data = $data;
        return $this;
    }

    /**
     * Use this to load this instance with data from other source
     * For example when running a manual query
     * @param $data
     */
    public function loadFromArray(array $data, $isRecordNew = false){
        foreach($data as $key => $value){
            $this->_data[$key] = $this->_cast($key,$value);
        }

        if($isRecordNew) $this->_isRecordNew = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function save(){

        if($this->_isRecordNew){

            // save the data
            $id = App::getMysql()
                ->table($this->getTableName())
                ->insert($this->_data);

            // set the id it got in this instance
            $this->_data[$this->getIdCol()] = $id;

            // store that it is not new anymore
            $this->_isRecordNew = false;
        } else {
            App::getMysql()
                ->table($this->getTableName())
                ->where($this->getIdCol(),"=",$this->getId())
                ->update($this->_data);
        }

        return $this;
    }

    public function delete(){
        if($this->_isRecordNew) throw new \Exception("Can't delete unsaved record");

        App::getMysql()
            ->table($this->getTableName())
            ->where($this->getIdCol(),"=",$this->getId())
            ->delete();

        return $this;
    }

    /**
     * @return Mysql\Collection
     */
    public function getCollection(){
        return new Model\Db\Mysql\Collection($this);
    }

    /**
     * @param $key
     * @return null|string
     */
    protected function _getData($key,$default = null){
        if(isset($this->_data[$key]))
            return $this->_cast($key, $this->_data[$key]);
        else
            return $default;
    }

    protected function _setData($key,$value){
        if(!$this->_isKeyAllowedInSchema($key)) throw new \Exception("Key not allowed in schema");

        $this->_data[$key] = $value;
    }

    /**
     * Cast $value based on schema for $key, and return
     * @param $key
     * @param $value
     */
    private function _cast($key,$value){

        if(!$this->_isKeyAllowedInSchema($key)) throw new \Exception("Key not allowed in schema");

        switch($this->_getDataTypeForKey($key)){
            case self::TYPE_BOOLEAN:
                return (bool) $value;
            case self::TYPE_FLOAT:
                return (float) $value;
            case self::TYPE_INT:
                return (int) $value;
            case self::TYPE_STRING:
                return (string) $value;
        }

        throw new \Exception("Undefined type");
    }

    /**
     * Get the datatype for $key, according to schema
     * @param $key
     * @return mixed
     * @throws \Exception
     */
    private function _getDataTypeForKey($key){

        if(!$this->_isKeyAllowedInSchema($key)) throw new \Exception("Key not allowed in schema");

        return $this->_schema[$key];
    }

    /**
     * Check if the key exists in schema
     * @param $key
     * @return bool
     */
    private function _isKeyAllowedInSchema($key){
        if(array_key_exists($key,$this->_schema))
            return true;

        return false;
    }

    /**
     * @return null|string
     */
    public function getId(){
        return $this->_getData($this->getIdCol());
    }

    /**
     * @return string
     */
    public function getIdCol() {
        return $this->_id_col;
    }

    /**
     * @return array
     */
    public function getSchema() {
        return $this->_schema;
    }

    /**
     * @return null
     */
    public function getTableName() {
        return $this->_table_name;
    }

}
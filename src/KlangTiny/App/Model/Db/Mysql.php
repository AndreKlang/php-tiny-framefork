<?php

namespace KlangTiny\App\Model\Db;

use KlangTiny\App;
use KlangTiny\App\Model;

abstract class Mysql extends Model {

    const TYPE_INT = 1;
    const TYPE_FLOAT = 2;
    const TYPE_STRING = 3;
    const TYPE_BOOLEAN = 4;

    // overwrite in each model as needed
    protected $_table_name = null;
    protected $_id_col = 'entity_id';
    protected $_schema = array(
        "entity_id" => self::TYPE_INT
    );

    /**
     * If this is true, save() will create a new record
     * If this is false, save() will use getId() to update a record
     * @var bool
     */
    protected $_isRecordNew = true;

    /** @var string[] */
    protected $_data = null;

    /** @var string[]  */
    protected $_changedColumns = array();

    public function load($identifier){

        // get data from DB
        $data = App::getMysql()
            ->table($this->getTableName())
            ->setFetchMode(\PDO::FETCH_ASSOC)
            ->find($identifier, $this->getIdCol());

        // make sure that we know this is loaded
        if(!empty($data)) {
            $this->_isRecordNew = false;
        }

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
        foreach($data as $key => $value) {
            $this->_data[$key] = $this->_cast($key, $value);
        }

        $this->_isRecordNew = ($isRecordNew);

        return $this;
    }

    /**
     * @return $this
     */
    public function save(){

        // if there is nothing to save, don't save
        if(!$this->isChanged()) {
            return $this;
        }

        // For new records, insert
        // For existing records, update
        if($this->_isRecordNew) {

            // save the data
            $newId = App::getMysql()
                ->table($this->getTableName())
                ->insert($this->_data);

            // set the id it got in this instance
            $this->_data[$this->getIdCol()] = $newId;

            // store that it is not new anymore
            $this->_isRecordNew = false;
        } else {

            // only save the changed columns
            // makes sure that we don't clear columns that was not loaded
            $data = array();
            foreach($this->_changedColumns as $key) {
                $data[$key] = $this->_getData($key);
            }

            App::getMysql()
                ->table($this->getTableName())
                ->where($this->getIdCol(), "=", $this->getId())
                ->update($data);
        }

        return $this;
    }

    /**
     * Delete row from db
     * @return $this
     * @throws \Exception
     */
    public function delete(){
        if($this->_isRecordNew) {
            throw new \Exception("Can't delete unsaved record");
        }

        App::getMysql()
            ->table($this->getTableName())
            ->where($this->getIdCol(), "=", $this->getId())
            ->delete();

        return $this;
    }

    /**
     * Get a collection instance.
     * If you need to implement custom logic in the Collection, overwrite this method
     * and return your own class, extending the Collection
     *
     * The $filter param is a callable, and gets the query object as it's only param
     * @see \Pixie\QueryBuilder\QueryBuilderHandler
     *
     * @var callable|null $filter
     * @return Mysql\Collection
     */
    public function getCollection($filter = null){
        return new Model\Db\Mysql\Collection($this, $filter);
    }

    /**
     * Get data by key, or all
     * @param null $key
     * @param null $default
     * @return array|null|string
     */
    public function getData($key = null, $default = null){

        if($key !== null) {
            return $this->_getData($key, $default);
        }

        return array_filter($this->_data, function ($arKey) use ($default) {
            return $this->_getData($arKey, $default);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * @param $key
     * @return null|string
     */
    protected function _getData($key, $default = null){
        if(isset($this->_data[$key])) {
            return $this->_cast($key, $this->_data[$key]);
        } else {
            return $default;
        }
    }

    /**
     * Set data for key
     * @param string $key
     * @param mixed $value
     * @throws \Exception
     */
    protected function _setData($key, $value){
        if(!$this->_isKeyAllowedInSchema($key)) {
            throw new \Exception("Key not allowed in schema");
        }

        $this->_registerChange($key, $value);

        $this->_data[$key] = $value;

        return $this;
    }

    /**
     * Check and register if a value has changed
     * If it has, that column is going to be saved when save() runs
     * @param $key
     * @param $value
     */
    protected function _registerChange($key, $value){
        if($value != $this->_getData($key) && !in_array($key, $this->_changedColumns)) {
            $this->_changedColumns[] = $key;
        }
    }

    /**
     * Checks if some value has changed since
     * @return bool
     */
    public function isChanged(){
        return !empty($this->_changedColumns);
    }

    /**
     * Cast $value based on schema for $key, and return
     * @param $key
     * @param $value
     */
    private function _cast($key, $value){

        if(!$this->_isKeyAllowedInSchema($key)) {
            throw new \Exception("Key not allowed in schema");
        }

        switch($this->_getDataTypeForKey($key)) {
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

        if(!$this->_isKeyAllowedInSchema($key)) {
            throw new \Exception("Key not allowed in schema");
        }

        return $this->_schema[$key];
    }

    /**
     * Check if the key exists in schema
     * @param $key
     * @return bool
     */
    private function _isKeyAllowedInSchema($key){
        if(array_key_exists($key, $this->_schema)) {
            return true;
        }

        return false;
    }

    /**
     * Get the row id, based on id_col
     * @return null|string
     */
    public function getId(){
        return $this->_getData($this->getIdCol());
    }

    /**
     * get the name of the id column as specified in $this->_id_col
     * @return string
     */
    public function getIdCol() {
        return $this->_id_col;
    }

    /**
     * Get the DB schema
     * @return array
     */
    public function getSchema() {
        return $this->_schema;
    }

    /**
     * Get the table name
     * @return null
     */
    public function getTableName() {
        return $this->_table_name;
    }
}

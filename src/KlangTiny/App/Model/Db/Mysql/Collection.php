<?php

namespace KlangTiny\App\Model\Db\Mysql;

use KlangTiny\App;

/**
 * Class Collection
 *
 * Thanks to:
 * http://techblog.procurios.nl/k/news/view/33914/14863/syntactic-sugar-for-mysqli-results-using-spl-iterators.html
 *
 * @package KlangTiny\App\Model\Db\Mysql
 */
class Collection implements \Iterator {

    protected $_position;
    protected $_currentRow;

    /** @var array[] */
    protected $_result = null;

    /** @var callable|null */
    protected $_filters = null;

    /** @var  App\Model\Db\Mysql */
    protected $_model;

    /**
     * Constructor
     * @param App\Model\Db\Mysql $model
     */
    public function __construct($model, $filters = null) {
        $this->_model = $model;
        $this->_filters = $filters;

    }

    /**
     * make the db request and store it in $this->_result
     */
    public function load(){

        // only load once
        if($this->_result !== null) return;

        // get a query for this table, and set the fetchmode
        $query = App::getMysql()
            ->table($this->_model->getTableName())
            ->setFetchMode(\PDO::FETCH_ASSOC);

        // if there is a filter callback registered, call it
        // that will modify the query
        if(is_callable($this->_filters)){
            /** @var callable $callback */
            $callback = $this->_filters;
            $callback($query);
        }

        // load the result into _result
        // NOTE: this can be bad for memory, but is unsure how to do it otherwise
        $this->_result = $query->get();
    }

    /**
     * Rewinds the internal pointer
     * This always run first, that's why we trigger a load
     */
    public function rewind() {

        $this->load();
        reset($this->_result);
    }

    /**
     * Moves the internal pointer one step forward
     */
    public function next() {
        next($this->_result);
    }

    /**
     * Returns true if the current position is valid, false otherwise.
     * @return bool
     */
    public function valid() {
        return key($this->_result) !== null;
    }

    /**
     * Returns the row that matches the current position
     * Load it into a model before returning
     * @return array
     */
    public function current() {

        // get the class of the model
        $modelName = get_class($this->_model);

        /**
         * Make a new instance of it
         * @var App\Model\Db\Mysql $return
         */
        $return = new $modelName();

        // load it with the current row-data & return
        return $return->loadFromArray(current($this->_result));
    }

    /**
     * Returns the current position
     * @return int
     */
    public function key() {
        return key($this->_result);
    }

    /**
     * Get row count
     * @return int
     */
    public function count(){
        $this->load();
        return count($this->_result);
    }

}
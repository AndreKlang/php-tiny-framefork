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

    /** @var \MySQLi_Result */
    protected $_result = null;

    /** @var null|\WhereClause */
    protected $_filters = null;

    /** @var  App\Model\Db\Mysql */
    protected $_model;

    /**
     * Constructor
     * @param App\Model\Db\Mysql $model
     * @param \WhereClause $filters
     */
    public function __construct($model, \WhereClause $filters = null) {
        $this->_model = $model;
        $this->_filters = $filters;

    }

    /**
     * make the db request and store it in $this->_result
     */
    public function load(){

        if($this->_result !== null) return;

        if($this->_filters) {
            $filter = $this->_filters;
        } else {
            $filter = "1=1";
        }


        $this->_result = App::getMysql()->queryRaw(
            "SELECT * FROM %1 WHERE %2",
            $this->_model->getTableName(),
            $filter
        );
    }

    /**
     * Destructor
     * Frees the Result object
     */
    public function __destruct() {
        $this->_result->free();
    }

    /**
     * Rewinds the internal pointer
     */
    public function rewind() {

        $this->load();

        // data_seek moves the Results internal pointer
        $this->_result->data_seek($this->_position = 0);

        // prefetch the current row
        // note that this advances the Results internal pointer.
        $this->_currentRow = $this->_result->fetch_array(MYSQLI_ASSOC);
    }

    /**
     * Moves the internal pointer one step forward
     */
    public function next() {
        // prefetch the current row
        $this->_currentRow = $this->_result->fetch_array(MYSQLI_ASSOC);

        // and increment internal pointer
        ++$this->_position;
    }

    /**
     * Returns true if the current position is valid, false otherwise.
     * @return bool
     */
    public function valid() {
        return $this->_position < $this->_result->num_rows;
    }

    /**
     * Returns the row that matches the current position
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
        return $return->loadFromArray($this->_currentRow);
    }

    /**
     * Returns the current position
     * @return int
     */
    public function key() {
        return $this->_position;
    }

    public function count(){
        return $this->_result->num_rows;
    }

}
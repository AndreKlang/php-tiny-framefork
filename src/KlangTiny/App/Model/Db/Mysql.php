<?php

namespace KlangTiny\App\Model\Db;
use KlangTiny\App;
use KlangTiny\App\Model;

class Mysql extends Model {

    // overwrite in each model as needed
    protected $_table_name = null;
    protected $_id_col = 'entity_id';

    /** @var string[] */
    protected $_data = null;

    public function load($id){
        $this->_data = App::getMysql()->queryFirstRow("SELECT * FROM %b WHERE %b = %i",$this->_table_name,$this->_id_col,$id);
        return $this;
    }

}
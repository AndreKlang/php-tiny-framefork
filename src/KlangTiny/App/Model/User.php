<?php

namespace KlangTiny\App\Model;

use KlangTiny\App\Model;

class User extends Model{

    protected $_table_name = 'user_entity';
    protected $_id_col = 'entity_id';

    public function getName(){
        return $this->_data["name"];
    }

}
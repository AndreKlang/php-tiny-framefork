<?php

namespace App\Model;

use App\Model;

class User extends Model{

    protected $_table_name = 'user_entity';
    protected $_id_col = 'entity_id';

    public function getName(){
        return $this->_data["name"];
    }

}
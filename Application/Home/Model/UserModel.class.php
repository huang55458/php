<?php
namespace Home\Model;

use Think\Model;

class UserModel extends Model
{

    protected $tableName = 'user';

    public function __construct () {
        parent::__construct();
    }
}
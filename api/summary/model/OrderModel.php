<?php

namespace api\summary\model;

use think\Model;
use think\model\concern\SoftDelete;

class OrderModel extends Model{
  use SoftDelete;
  protected $autoWriteTimestamp = 'datetime';
  protected $deleteTime = 'delete_time';
  protected $defaultSoftDelete = '0000-00-00 00:00:00';

  public function item(){
    return $this->hasMany('ItemModel','id','item_id');
  }
  public function item1(){
    return $this->hasOne('ItemModel','id','item_id');
  }

  public function user(){
    return $this->hasOne('UserModel','id','user_id');
  }
}
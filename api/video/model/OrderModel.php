<?php

namespace api\video\model;

use think\Model;

class OrderModel extends Model{
  protected $autoWriteTimestamp = true;

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
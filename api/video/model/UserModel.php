<?php

namespace api\video\model;

use think\Model;

class UserModel extends Model
{
  public function code(){
      return $this->hasOne('UserPromotionModel','user_id','id');
  }
}

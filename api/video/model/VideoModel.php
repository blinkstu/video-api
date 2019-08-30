<?php

namespace api\video\model;

use think\Model;

class VideoModel extends Model
{
  protected $autoWriteTimestamp = true;

  public function category(){
    return $this->hasOne('CategoryModel','id','category_id');
  }
}

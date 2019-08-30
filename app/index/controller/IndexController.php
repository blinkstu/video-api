<?php

namespace app\index\controller;
use think\Controller;
use think\facade\View;

class IndexController extends Controller
{
  public function index(){
    return 'None';
    //return View('/index');
  }
}
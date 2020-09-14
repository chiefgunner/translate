<?php
/**
 * Created by PhpStorm.
 * User: chiefgunner
 * Date: 2020/9/9
 * Time: 2:18 AM
 */

namespace app\model;


use think\Model;

class Goods extends Model
{
    protected $autoWriteTimestamp = true;
    public function getImgAttr($value)
    {

        return config('app.app_host').$value;
        //return request()->domain().$value;
    }
    //
    public function getSrcAttr($value,$data)
    {
        return $data['img'];
    }
}
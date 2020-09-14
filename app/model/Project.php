<?php
/**
 * Created by PhpStorm.
 * User: chiefgunner
 * Date: 2020/9/9
 * Time: 9:06 PM
 */

namespace app\model;


use think\Model;

class Project extends Model
{
    protected $autoWriteTimestamp = true;

    public function setMaskAttr($value){
        $data = [
            '低优先级'=>0,
            '正常排队'=>1,
            '高优先级'=>2,
            '立即解决'=>3
        ];
        return $data[$value];
    }
    public function getMaskAttr($value)
    {
        $data = ['低优先级','正常排队','高优先级','立即解决'];
        return $data[$value];
    }
    public function getTypeAttr($value)
    {
        $data = ['info','primary','warning','danger'];
        return $data[$value];
    }
    public function getImgAttr($value)
    {
        return config('app.app_host').$value;
    }
    public function getSrcAttr($value,$data)
    {
        return $data['img'];
    }


    public function item()
    {
        return $this->hasMany(Item::class,'id','project_id');
    }
}
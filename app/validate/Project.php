<?php
/**
 * Created by PhpStorm.
 * User: chiefgunner
 * Date: 2020/9/10
 * Time: 12:03 AM
 */

namespace app\validate;


use think\Validate;

class Project extends Validate
{
    protected $rule = [
        'id|ID'=>'require',
        'name|请填写名称'=>'require',
        'img|请上传图片'=>'file|fileExt:jpg,jpeg,png',
        'mask|请选择优先级别'=>"in:低优先级,正常排队,高优先级,立即解决",
        "item_ids|请选择子项"=>'require'
    ];
    protected $scene = [
        'addProject'=>['name','img','mask'],
        'addItems'=>['item_ids'],
        "editProject"=>['id','name','mask']
    ];
}
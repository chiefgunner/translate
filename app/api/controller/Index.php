<?php
namespace app\api\controller;

use app\BaseController;
use app\common\lib\Re;
use app\model\Goods;
use app\model\Project;
use app\model\Item;
use think\facade\Filesystem;


class Index extends BaseController
{
    protected function getLabel($mask)
    {
        $arr = [
            '总计',
            '立即解决',
            '高优先级',
            '正常排队',
            '低优先级',
        ];
        return $arr[$mask];
    }
    protected function curl_post($url = '',$param = '')
    {
        if (empty($url) || empty($param)) {
            return false;
        }

        $postUrl = $url;
        $curlPost = $param;
        // 初始化curl
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $postUrl);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        // 要求结果为字符串且输出到屏幕上
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        // post提交方式
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
        // 运行curl
        $data = curl_exec($curl);
        curl_close($curl);

        return $data;
    }
    protected function getAccessToken()
    {
        $token = cache('access_token');
        if(! $token)
        {
            $data = [
                'grant_type'=>"client_credentials",
                'client_id'=>env('baidu.client_id'),
                'client_secret'=>env('baidu.client_secret')
            ];
            $url = 'https://aip.baidubce.com/oauth/2.0/token';
            $data = $this->curl_post($url,$data);

            $data = json_decode($data,true);
            cache('token',$data,$data['expires_in']);
            cache('access_token',$data['access_token'],$data['expires_in']);
            $token = $data['access_token'];
        }

        return $token;
    }
    public function getChart()
    {
        /*$res = [
            'labels'=>['木头','碎石','石油','电','电线','Si','C'],
            'datasets'=>[
                [
                    'label'=>'GitHub Commits',
                    'backgroundColor'=>"#f87979",
                    'data'=>[rand(1,100), rand(1,100), rand(90,100), rand(1,100), rand(1,100), rand(1,100), rand(1,100)]
                ]
            ]
        ];*/
//        return Re::success($res);
        $id = input('id','','trim');
        $mask = (4 - $id);
        if($mask ==4){
            $project = Project::select()->toArray();
        }else{
            $project = Project::where('mask',$mask)->select()->toArray();
        }
        $ids = implode(array_column($project,'id'),',');

        $items = Item::with('goods')->where('status',0)->whereIn('project_id',$ids)->select()->toArray();
        $labels = $data = [];

        if($items)
        {
            $goods = [];
            foreach($items as $val)
            {
                if(isset($goods[$val['goods_id']])){
                    $goods[$val['goods_id']]['num'] += $val['num'];             //项目所需数量
                }else{
                    $goods[$val['goods_id']]['name'] = $val['goods']['name'];
                    $goods[$val['goods_id']]['total'] = $val['goods']['total']; //实际收集数量
                    $goods[$val['goods_id']]['num'] = $val['num'];              //项目所需数量
                }
            }

            foreach($goods as $val){
                $labels[] = $val['name'];
                $data[] = round( round($val['total'] / $val['num'] , 3) * 100  , 1 );
            }
        }

        $result = [
            'labels'=>$labels,
            'datasets'=>[
                [
                    'label'=>$this->getLabel($id),
                    'backgroundColor'=>'#f87979',
                    'data'=>$data
                ]
            ]
        ];

        return Re::success($result);
    }
    public function ai()
    {
        $img = input('image','','trim');
        if(! $img) return Re::error('缺少参数');

        $token = $this->getAccessToken();
        $url = 'https://aip.baidubce.com/rest/2.0/ocr/v1/accurate_basic?access_token=' . $token;

        $bodys = array(
            'image' => $img
        );
        $res = $this->curl_post($url, $bodys);
        $data = json_decode($res,true);
        $word = array_column($data['words_result'],'words');


        $goodsName = cache('goodsName');
        if(! $goodsName){
            $goods = Goods::select()->toArray();
            $goodsName = array_column($goods,'es','id');
            cache('goodsName',$goodsName,600);
        }

        $w = $n = [];
        foreach($word as $val)
        {
            $val = trim($val);
            if(in_array($val,$goodsName)){
                $w[] = $val;
            }else{
                if(is_numeric($val)){
                    $n[] = $val;
                }else if( is_numeric(preg_replace('/\D/','',$val)) ){
                    //preg_match('/^\d+\D+$/',$val)
                    //$n[] = preg_replace('/\D/','',$val);
                    preg_match_all('/\d+/',$val,$all);
                    $n = array_merge($n,$all[0]);
                }
            }
        }
        $saveData = $list = [];
        $goods = array_flip($goodsName);
        foreach($w as $k=>$v){
            if( isset($n[$k]) ){
                $list[] = [
                    'id'=>$goods[$v],
                    'name'=>$v,
                    'num'=>$n[$k]
                ];
                $saveData[] = [
                    'id'=>$goods[$v],
                    'total'=>$n[$k]
                ];
            }
        }
        $goods = new Goods();
        $goods->saveAll($saveData);

        return Re::success(['baidu'=>$data,'list'=>$list,]);
    }
    public function fanyi()
    {
        $q = input('q','','trim');
        $appid = env('baidu.appid');
        $salt = rand(100000,999999);
        $secret = env('baidu.secret');
        $data = [
            'q'=>$q,
            'from'=>'en',
            'to'=>'zh',
            'appid'=>$appid,
            'salt'=>$salt,
            'sign'=>md5( $appid . $q . $salt . $secret )
        ];
        $url = "https://fanyi-api.baidu.com/api/trans/vip/translate";
        $data = $this->curl_post($url,$data);
        $data = json_decode($data,true);

        if(! isset($data['error_code'])){
            return Re::success($data['trans_result']);
        }else{
            return Re::error($data['error_msg']);
        }
    }
    public function goods()
    {
        $goods = Goods::select();
        $res = $goods->toArray();
        if(! $res){
            $res = [];
        }

        return Re::success($res);
    }
    public function delGoods()
    {
        $id = input('id');
        $goods = Goods::find($id);
        if(! $goods){
            return Re::error('未找到该数据');
        }else{
            $goods->delete();
            unlink('.'.$goods->getData('img'));
        }
//        $res = unlink('./storage/images/20200909/45f5e0880e00cc31d1c55b4785ff7a3b.jpg');
        return Re::success();
    }

    public function addGoods()
    {
        $data = [];
        $data['es'] = input('es','','trim');
        $data['name'] = input('name','','trim');
        $data['img'] = $files = request()->file('img');
        /*
        try {
            validate(Goods::class)->check($data);
        } catch (\think\exception\ValidateException $e) {
            // 验证失败 输出错误信息
            return Re::error($e->getMessage());
        }
        */

        $validate = (new \app\validate\Goods());
        if(! $validate->check($data)){
            return Re::error($validate->getError());
        }
        //保存数据
        if(Goods::where('es',$data['es'])->find() ){
            return Re::error('该数据已存在');
        }
        //上传图片呢
        $savename = '/storage/'.Filesystem::disk('public')->putFile('images',$files);

        if(! $savename){
            return Re::error('上传失败');
        }

        $goods = new Goods();
        $goods->es = $data['es'];
        $goods->name = $data['name'];
        $goods->img = $savename;
        $res = $goods->save();
        if($res){
            return Re::success('OK');
        }
    }

    //project

    public function project()
    {
        $data = Project::field('id,name,mask,img,mask as type,list')
            ->where('status',0)
            ->order(['mask'=>'desc','list'=>'asc'])
            ->select()
            ->toArray();
        if($data)
        {
            $ids = implode(array_column($data,'id'),',');
            $itemAndGoods = Item::with('goods')->where('status',0)->whereIn('project_id',$ids)->select()->toArray();
            $percent = [];
            foreach($itemAndGoods as $val)
            {
                if($val['num'] <= $val['goods']['total']){
                    $percent[$val['project_id']][] = 100;
                }else{
                    $percent[$val['project_id']][] = round( round($val['goods']['total']/$val['num'],3)*100 ,1);
                }
            }
            foreach($data as &$val)
            {
                if(isset($percent[$val['id']]))
                {
                    $val['percent'] = round( array_sum($percent[$val['id']]) / count($percent[$val['id']]) ,1 );
                }else{
                    $val['percent'] = 0;
                }
            }
        }
//        dump($itemAndGoods);
//        dump($percent);
//        dump($data);
        return Re::success($data);
    }

    public function addProject()
    {
        $data = [];
        $data['name'] = input('name','','trim');
        $data['img'] = request()->file('img');
        $data['mask'] = input('mask','','trim');

        $validate = (new \app\validate\Project())->scene('addProject');
        if(! $validate->check($data)){
            return Re::error($validate->getError());
        }

        $res = Project::where('name',$data['name'])->find();
        if($res){
            return Re::error('该数据已存在');
        }

        $savename = '/storage/'.Filesystem::disk('public')->putFile('project',$data['img']);
        if(! $savename){
            return Re::error('上传失败');
        }
        //add data
        $project = new Project();
        $project->name = $data['name'];
        $project->mask = $data['mask'];
        $project->img = $savename;
        $res = $project->save();
        if($res){
            return Re::success('OK');
        }
    }
    public function editProject()
    {
        $data['id'] = input('id','','trim');
        $data['name'] = input('name','','trim');
        $data['mask'] = input('mask','','trim');

        $validate = (new \app\validate\Project())->scene('editProject');
        if(! $validate->check($data)){
            return Re::error($validate->getError());
        }
        $project = Project::find($data['id']);
        if(! $project){
            return Re::error('未找到该记录');
        }

        $res = Project::where('name',$data['name'])->find();
        if($res && $res->id != $data['id']){
            return Re::error('该数据已存在');
        }

        $project->name = $data['name'];
        $project->mask = $data['mask'];
        $project->save();

        return Re::success();
    }
    public function delProject()
    {
        $id = input('projectId','','trim');
        $project = Project::find($id);
        if(! $project){
            return Re::error('未找到该记录');
        }
        //删除图片
        $src = $project->getData('img');
        unlink('.'.$src);
        //删除item
        Item::where('project_id',$id)->delete();
        //删除porject
        $project->delete();

        return Re::success();
    }
    public function reset()
    {
        $id = input('projectId','','trim');
        $project = Project::find($id);
        if(! $project){
            return Re::error('未找到该记录');
        }
        //
        Item::update(['status'=>1],['project_id'=>$id,'status'=>0]);//->where('project_id',$id);
        return Re::success();
    }

    public function addItem()
    {
        $data = [];
        $data['projectId'] = $projectId = input('projectId','','trim');
        $data['goodsId'] = $goodsId = input('goods_id','','trim');
        $data['num'] = $num = input('num','','trim');

        $validate = (new \app\validate\Item());
        if(! $validate->check($data)){
            return Re::error($validate->getError());
        }
        $project = Project::find($projectId);
        $goods = Goods::find($goodsId);

        if(! $project || ! $goods){
            return Re::error('未找到该记录');
        }
        $item = Item::where([
            ['project_id','=',$projectId],
            ['goods_id','=',$goodsId],
            ['status','=',0]
        ])->find();

        if($item){
            return Re::error('存在相同子项');
        }
        //保存item
        $item = new Item();
        $item->project_id = $projectId;
        $item->goods_id = $goodsId;
        $item->num = $num;
        $res = $item->save();
        //修改project
        if(! $res){
            return Re::error('新增失败');
        }
        $itemIds = $project->item_ids;
        if(! $itemIds){
            $itemIds = $item->id;
        }else{
            $itemIds = $itemIds.','.$item->id;
        }

        $project->item_ids = $itemIds;
        $project->save();

        return Re::success();
    }
    public function delItem()
    {
        $id = input('id','','trim');
        $goodsId = input('goods_id','','trim');
        $item = Item::where([
            ['goods_id','=',$goodsId],
            ['id','=',$id]
        ])->find();
        if(! $item){
            return Re::error('未找到该记录');
        }

        $item->delete();
        return Re::success();
    }
    public function editItem()
    {
        $id = input('id','','trim');
        if(! $id){
            return Re::error('缺少id');
        }
        $data['projectId'] = $projectId = input('projectId','','trim');
        $data['goodsId'] = $goodsId = input('goods_id','','trim');
        $data['num'] = $num = input('num','','trim');

        $validate = (new \app\validate\Item());
        if(! $validate->check($data)){
            return Re::error($validate->getError());
        }

        $item = Item::find($id);
        $goods = Goods::find($data['goodsId']);
        if(! $item || ! $goods){
            return Re::error('未找到该记录');
        }

        $other = Item::where([
            ['project_id','=',$data['projectId']],
            ['goods_id','=',$data['goodsId']],
            ['status','=',0]
        ])->find();
        if($other && $other->id != $id){
            return Re::error('存在相同子项');
        }
        $item->goods_id = $data['goodsId'];
        $item->num = $data['num'];
        $item->save();

        return Re::success();


    }

    public function showItem()
    {
        $projectId = input('projectId','','trim');
        if(! $projectId){
            return Re::error('参数错误');
        }
        $project = Project::find($projectId);
        $itemIds = $project->item_ids;

        $result = [];
        if($itemIds){
            $result = Item::with('goods')->where('status',0)->whereIn('id',$itemIds)->select()->toArray();
            foreach($result as &$val)
            {
                if($val['num'] <= $val['goods']['total']){
                    $val['percent'] = 100;
                }else{
                    $val['percent'] = round( $val['goods']['total'] / $val['num'] ,3 );
                    $val['percent'] = round($val['percent'] * 100,1);
                }
            }
        }

        return Re::success($result);
    }

}

<?php
namespace Org\Util;

class RedisDynamicSave extends RedisPage{

    /**
     * @param $key    //区分（如点赞，分享等）
     * @param $member //标示 (如会员id，openid等)
     * @param $index  //数据索引
     * @param array $memberData
     * @param bool $auto true 自动加减 false 递增
     * @return mixed
     */
    public function dynamicSave($key,$member,$index, $memberData = [],$auto = true){
        $date = date('Y-m-d H:i:s');
        //判断会员是否点赞
        $checkId = $this->redis->hGet($this->prefix.$key.$member.'data_'.$index, $this->prefix.$key.'detail');
        if ($checkId == 1 && $auto) {
            //取消
            $rNum = $this->redis->hIncrBy($this->prefix.$key.$member.'data_'.$index, $this->prefix.$key.'detail', -1);
            if($rNum == 0){
                //文章点赞数减一
                $r = $this->redis->zIncrBy($this->prefix.$key,-1,$index);
                $re['status'] = true;
                $re['info'] = 'minus';
                $re['result'] = $r;
            }else{
                $re['status'] = false;
                $re['info'] = '取消失败';
            }
        } else {
            //添加
            $memberData[$this->prefix.$key.'detail'] = 1;
            $memberData[$this->prefix.$key.'time'] = $date;
            //设置会员给文章点赞记录
            $rNum = $this->redis->hMset($this->prefix.$key.$member.'data_'.$index, $memberData);
            if($rNum == 1){
                //文章点赞数加一
                $r = $this->redis->zIncrBy($this->prefix.$key,1,$index);
                $re['status'] = true;
                $re['info'] = 'plus';
                $re['result'] = $r;
            }else{
                $re['status'] = false;
                $re['info'] = '添加失败';
            }
        }
        $dynamicSave = $this->redis->hGetAll($this->prefix.'dynamicSave');
        if(!in_array($key,$dynamicSave)){
            array_push($dynamicSave,$key);
            $this->redis->hMset($this->prefix.'dynamicSave', $dynamicSave);
        }
        return $re;
    }

    /**
     * 列表显示数据
     * @param $key //排序条件
     * @param int $page //页码
     * @param int $length //每页显示条数
     * @param string $member //用户标示
     * @param string $orderby // 默认倒叙 desc
     * @return mixed
     */
    public function getList($key,$page = 1,$length = 10,$member = null,$orderby = 'desc'){
        $page = intval($page);
        $length = intval($length);
        $total = $this->getTotal($key);//总条数
        $total_pages = ceil($total/$length);//总页码
        $pageList['page'] = $page;//当前页码
        $pageList['pageTotal'] = $total_pages;//总页码
        $pageList['numTotal'] = $total;//总条数
        $pageList['data'] = [];//数据列表
        if($page > $total_pages){
            $page = $total_pages;
            $pageList['page'] = $page;
        }
        $bpage = ($page-1)*$length;//开始条数
        $end = ($bpage+$length)-1;//结束条数
        if($orderby == 'desc'){
            $range = $this->redis->zRevRange($this->prefix.$key,$bpage,$end);//desc 从大到小
        }else{
            $range = $this->redis->zRange($this->prefix.$key,$bpage,$end);//asc 从小到大
        }
        foreach($range as $k=>$v){
            $list = $this->redis->hGetAll($this->prefix.'list_detail_'.$v);
            $dynamicSave = $this->redis->hGetAll($this->prefix.'dynamicSave');
            foreach ($dynamicSave as $kk=>$vv){
                //获取用户点赞
                if(!empty($member)){
                    $checkId = $this->redis->hGet($this->prefix.$vv.$member.'data_'.$v, $this->prefix.$vv.'detail');
                    if($checkId){
                        $list['is_'.$vv] = 'yes';
                    }else{
                        $list['is_'.$vv] = 'no';
                    }
                }
                //获取点赞数
                $flag = $this->redis->zScore($this->prefix.$vv,$v);
                if($flag){
                    $list[$vv] += $flag;
                }
            }
            $pageList['data'][$k] = $list;
        }
        return $pageList;
    }
    /**
     * 获取详情数据
     * @param $index
     * @param $member
     * @return mixed
     */
    public function getDataDetail($index,$member){
        $detail = $this->redis->hGetAll($this->prefix.'detail_'.$index);
        $dynamicSave = $this->redis->hGetAll($this->prefix.'dynamicSave');
        foreach ($dynamicSave as $kk=>$vv){
            //获取用户点赞
            if(!empty($member)){
                $checkId = $this->redis->hGet($this->prefix.$vv.$member.'data_'.$index, $index);
                if($checkId){
                    $list['is_'.$vv] = 'yes';
                }else{
                    $list['is_'.$vv] = 'no';
                }
            }
            //获取点赞数
            $flag = $this->redis->zScore($this->prefix.$vv,$index);
            if($flag){
                $list[$vv] = $flag;
            }else{
                $list[$vv] = 0;
            }
        }
        return $detail;
    }

    /**
     * 删除动态添加的key
     * @param $key
     * @return mixed
     */
    public function delDynamicSave($key){
        $dynamicSave = $this->redis->hGetAll($this->prefix.'dynamicSave');
        $hashKey = array_search($key,$dynamicSave);
        $this->redis->hDel($this->prefix.'dynamicSave',$hashKey);
    }

}

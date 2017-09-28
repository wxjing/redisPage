<?php
namespace Org\Util;

class RedisPage {

    public $redis = null;
    public $host = 'localhost';
    public $port = '6379';
    public $password = '';
    public $dbindex = '1';
    /**
     * 前缀
     * @var string
     */
    public $prefix = 'redis_page_';

    public function __construct($host,$auth,$port,$dbindex)
    {
        if(!empty($host)){
            $this->host = $host;
        }
        if(!empty($auth)){
            $this->auth = $auth;
        }
        if(!empty($port)){
            $this->port = $port;
        }
        if(!empty($dbindex)){
            $this->dbindex = $dbindex;
        }
        $this->connectRedis();
    }

    /**
     * 连接redis
     */
    public function connectRedis(){
        $this->redis = new \Redis();
        $this->redis->connect($this->host, $this->port);
        $this->redis->auth($this->password);
        $this->redis->select($this->dbindex);
    }

    /**
     * 添加排序条件数据
     * @param null $key
     * @param null $score  排序的数据
     * @param null $value  数据
     */
    public function setSortData($key = null,$score = null,$value = null){
        $this->redis->zAdd($this->prefix.$key,$score,$value);
    }

    /**
     * 从排序条件中删除数据
     * @param null $key  排序条件
     * @param null $value  数据
     */
    public function delSortData($key = null,$value = null){
        $this->redis->zRem($this->prefix.$key,$value);
        $check = $this->redis->exists($this->prefix.'detail_'.$value);
        if($check){
            $this->redis->del($this->prefix.'detail_'.$value);
        }
        $check_list = $this->redis->exists($this->prefix.'list_detail_'.$value);
        if($check_list){
            $this->redis->del($this->prefix.'list_detail_'.$value);
        }
    }

    /**
     * 添加详情数据
     * @param $index
     * @param $data
     */
    public function setDataDetail($index,$data){
        $this->redis->hMset($this->prefix.'detail_'.$index,$data);
    }

    /**
     * 添加列表显示数据
     * @param $index
     * @param $data
     */
    public function setListDetail($index,$data){
        $this->redis->hMset($this->prefix.'list_detail_'.$index,$data);
    }

    /**
     * 列表显示数据
     * @param $key //排序条件
     * @param int $page //页码
     * @param int $length //每页显示条数
     * @param string $orderby // 默认倒叙 desc
     * @return mixed
     */
    public function getList($key,$page = 1,$length = 10,$orderby = 'desc'){
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
            $pageList['data'][$k] = $this->redis->hGetAll($this->prefix.'list_detail_'.$v);
        }
        return $pageList;
    }

    /**
     * 获取详情数据
     * @param $index
     * @return mixed
     */
    public function getDataDetail($index){
        return $this->redis->hGetAll($this->prefix.'detail_'.$index);
    }

    /**
     * 获取排序数据总数量
     * @param $key
     * @return mixed
     */
    public function getTotal($key){
        return $this->redis->zCard($this->prefix.$key);
    }

    public function __destruct()
    {
        $this->redis->close();
    }
}

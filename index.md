```
public function index(){
        $RedisPage = new RedisDynamicSave('','','','1');
        $RedisPage->prefix = 'test';


        $array = [
            array('id'=>'1','name'=>'G','guanzhu' => '1'),
            array('id'=>'2','name'=>'F','guanzhu' => '1'),
            array('id'=>'3','name'=>'E','guanzhu' => '1'),
            array('id'=>'4','name'=>'D','guanzhu' => '1'),
            array('id'=>'5','name'=>'C','guanzhu' => '1'),
            array('id'=>'6','name'=>'B','guanzhu' => '1'),
            array('id'=>'7','name'=>'A','guanzhu' => '1')
        ];
        foreach ($array as $k=>$v){
            $RedisPage->setSortData('id',$v['id'],$v['id']);
            $RedisPage->setListDetail($v['id'],$v);
            $RedisPage->setDataDetail($v['id'],$v);
        }
//        $RedisPage->delSortData('id','1');
//        $RedisPage->delData('1');

//        $r = $RedisPage->getListDetail('1','wxjing');
//        $r = $RedisPage->getDataDetail('1');

        // guanzhu {wxjing,2} {wxjing,3}
        $r = $RedisPage->dynamicSave('guanzhu','wxjing','2');
        $r = $RedisPage->getList('id',1,50,'wxjing','asc');
//        $r = $RedisPage->delDynamicSave('b');
        $this->ajaxReturn($r,'JSON');

    }
```

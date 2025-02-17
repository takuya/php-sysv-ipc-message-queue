<?php

namespace Tests\PhpSysVMessageQueue\Units;

use Takuya\PhpSysvMessageQueue\IPCMsgQueue;
use Tests\PhpSysVMessageQueue\TestCase;
use function Takuya\Helpers\str_rand;

class SysVMessageQueueTest extends TestCase {
  protected IPCMsgQueue $q;
  protected int $cnt;
  
  protected function setUp():void {
    parent::setUp();
    $this->cnt = `ipcs -q | wc -l `;
    $this->q = new IPCMsgQueue(str_rand());
  }
  
  protected function tearDown():void {
    parent::tearDown();
    $size = $this->q->size();
    $this->assertEquals(0,$size);
    $destroyed = $this->q->destroy();
    $this->assertTrue($destroyed);
    $this->assertEquals($this->cnt, `ipcs -q | wc -l `);
    
  }
  
  public function test_create_send_receive_destroy_queue() {
    $q = $this->q;
    //
    $msg = rand();
    $q->push($msg);
    $q->pop();
    $ret = $q->currentValue();
    $this->assertEquals($msg, $ret);
  }
  public function test_push_pop_object_into_queue(){
    $q = $this->q;
    //
    $obj = new \stdClass();
    $obj->a = 1;
    $q->push($obj);
    $q->pop();
    $ret = $q->currentValue();
    $this->assertEquals($obj, $ret);
    
  }
  public function test_push_pop_string_into_queue(){
    $q = $this->q;
    $max = 1024;
    foreach( range(32,$max,32) as $len){
      $msg = str_rand($len);
      $q->push($msg);
      $q->pop(null,strlen(serialize($msg)));
      $this->assertEquals($msg, $q->currentValue());
    }
  }
  public function test_shutdown_restart_queue(){
    $q = $this->q;
    $arr = [str_rand(),str_rand(),str_rand()];
    foreach ($arr as $e) {
      $q->push($e);
    }
    $dump = $q->dumpQueue();
    $q->destroy();
    $q = $this->q = new IPCMsgQueue(str_rand());
    $q->loadQueue($dump);
    $size = $q->size();
    $loaded = $q->dumpQueue();
    //
    $this->assertEquals(serialize($arr),$dump);
    $this->assertEquals(sizeof($arr),$size);
    $this->assertEquals($dump,$loaded);
    $this->assertEquals(serialize($arr),$loaded);
  }
  public function test_save_load_queue(){
    $q = $this->q;
    $f = sys_get_temp_dir().DIRECTORY_SEPARATOR.str_rand().'.phpdump';
    $arr = [str_rand(),str_rand(),str_rand()];
    foreach ($arr as $e) {
      $q->push($e);
    }
    $q->save($f);
    $q->destroy();
    $q = $this->q = new IPCMsgQueue(str_rand());
    $q->load($f);
    $size = $q->size();
    $loaded = $q->dumpQueue();
    $this->assertEquals(sizeof($arr),$size);
    $this->assertEquals(serialize($arr),$loaded);
  }
}


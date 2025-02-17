<?php

namespace Tests\PhpSysVMessageQueue\Units;

use Takuya\PhpSysvMessageQueue\IPCMsgQueue;
use Tests\PhpSysVMessageQueue\TestCase;
use function Takuya\Helpers\str_rand;

class MultiProcessTest extends TestCase {

  protected int $cnt;
  
  protected function setUp():void {
    parent::setUp();
    $this->cnt = `ipcs -q | wc -l `;
  }
  
  protected function tearDown():void {
    parent::tearDown();
    $this->assertEquals($this->cnt, `ipcs -q | wc -l `);
    
  }
  public function test_same_name_same_queue(){
    $name = str_rand();
    $msg = str_rand();
    $q1 = new IPCMsgQueue($name);
    $q2 = new IPCMsgQueue($name);
    $q1->push($msg);
    $this->assertEquals($q1->size(),$q2->size());
    $pop = $q2->pop();
    $q1->destroy();
    $q2->destroy();
    $this->assertEquals($msg,$pop);
  }
  public function test_multi_process_forked(){
    $name = str_rand();
    $msg = str_rand();
    if (($pid = pcntl_fork())===false){
      throw new \Exception('fork failed');
    }
    if ( $pid===0 ){
      $q = new IPCMsgQueue($name);
      $q->push($msg);
      while(!$q->empty()){
        usleep(100);
      }
      $q->destroy();
      exit(0);
      
    }
    $q = new IPCMsgQueue($name);
    $pop = $q->pop();
    $q->destroy();
    pcntl_waitpid($pid,$st);
    $this->assertEquals($msg,$pop);
    $this->assertEquals(0,$st);
    $this->assertFalse(posix_kill($pid,0));
  
  }
}


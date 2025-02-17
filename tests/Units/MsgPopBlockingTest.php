<?php

namespace Tests\PhpSysVMessageQueue\Units;

use Takuya\PhpSysvMessageQueue\IPCMsgQueue;
use Tests\PhpSysVMessageQueue\TestCase;
use function Takuya\Helpers\str_rand;
use function Takuya\Helpers\get_process_info;

class MsgPopBlockingTest extends TestCase {
  
  public function test_pop_blocked() {
    $cnt = `ipcs -q | wc -l `;
    $sig = SIGABRT;
    pcntl_async_signals(true);
    $pid = pcntl_fork();
    $q = new IPCMsgQueue(str_rand());
    if($pid===0){
      pcntl_signal($sig,fn()=>$q->destroy());
      $q->pop();// blocked
      exit(1);// never reached.
    }
    usleep(1000);// ensure forked.
    $q->destroy();
    $ret = get_process_info($pid);
    
    // assert sleeping.
    $this->assertEquals('S',$ret['STAT'][0]);
    // kill sleeping process.
    posix_kill($pid,$sig);
    pcntl_waitpid($pid,$st);
    $ret = posix_kill($pid,0);
    $this->assertFalse($ret);
    $this->assertEquals($cnt, `ipcs -q | wc -l `);
  }
}


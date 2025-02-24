<?php

namespace Takuya\PhpSysvMessageQueue;

class IPCMsgQueue {
  
  protected int               $ipc_key;
  protected \SysvMessageQueue $_q;
  protected mixed             $lastValue;
  
  protected function key() {
    if( empty($this->ipc_key) ) {
      $seed = crc32($this->name);
      mt_srand($seed);
      $random_unsigned_int32 = mt_rand(0, PHP_INT_MAX) & 0x7FFFFFFF;
      mt_srand(time());
      $this->ipc_key = $random_unsigned_int32;
    }
    
    return $this->ipc_key;
  }
  
  public function __construct( public string $name, ) {
    if( ! ( $q = msg_get_queue($this->key()) ) ) {
      throw new \RuntimeException('msg_get_queue failed.');
    }
    $this->_q = $q;
  }
  
  public function push( mixed $data, int $priority = 100 ):bool {
    return msg_send($this->_q, $priority, $data);
  }
  
  public function pop( $priority = 100, $max_bytes = 1024 ) {
    $result = msg_receive($this->_q, $priority, $rcv_type, $max_bytes, $data);
    if( ! $result ) {
      return null;
    }
    $this->lastValue = $data;
    
    return $data;
  }
  public function empty():bool{
    return $this->size()==0;
  }
  
  public function size():?int {
    $stat = $this->status();
    
    return $stat["msg_qnum"] ?? null;
  }
  
  public function currentValue() {
    return $this->lastValue;
  }
  
  public function status():bool|array {
    return msg_stat_queue($this->_q);
  }
  
  public function available():bool {
    return msg_queue_exists($this->key());
  }
  
  public function all( $priority = 100, $max_bytes = 1024 ):array {
    $arr = [];
    while($this->size() > 0) {
      $arr[] = $this->pop($priority, $max_bytes);
    }
    
    return $arr;
  }
  
  public function dumpQueue( $priority = 100, $max_bytes = 1024 ):?string {
    return $this->size() != 0 ? serialize($this->all($priority, $max_bytes)) : null;
  }
  
  public function loadQueue( string $serialized_array ) {
    $arr = unserialize($serialized_array);
    foreach ($arr as $e) {
      $this->push($e);
    }
    return sizeof($arr);
  }
  
  public function save( string $file, $priority = 100, $max_bytes = 1024 ):bool {
    if($dump = $this->dumpQueue($priority, $max_bytes) ){
      return file_put_contents($file, $dump);
    }
    return false;
  }
  
  public function load( string $file ) {
    if (!file_exists($file)){
      return false;
    }
    if( filesize($file) < 3 ) {
      throw new \InvalidArgumentException('file is empty');
    }
    return $this->loadQueue(file_get_contents($file));
  }
  
  public function destroy():bool {
    return msg_remove_queue($this->_q);
  }
}

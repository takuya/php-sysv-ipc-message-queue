# php-sysv-ipc-message-queue
php sysv ipc message queue wrapper

## IPC Message Queue for php

SysV IPC Message queue is available as php function. This package is Object Class wrapping `msg_send`.

This repository wrap function and frequently appearing Usage.

## Installing 

from Packagist.

from GitHub.


## Examples.

- Initialize and push 
- Save and Load for system ShutDown.
- Multi process (inter process).

#### Initialize and push to Queue
initialize and send data to queue.
```php
$q = new IPCMsgQueue('my-named-queue');
$msg = 'random string';
$q->push($msg);
```

Since `msg_send` is supporting `serialize`. we can push everything.

```php
$q = new IPCMsgQueue('my-named-queue');
// send array 
$msg = ['a'=>1];
$q->push($msg);
// send object
$obj = new stdClass();
$obj->name = 'takuya';
$msg = $obj;
$q->push($msg);
```
We can get queue message as is sent.
```php
$q = new IPCMsgQueue('my-named-queue');
$arr = ['a'=>1];
$q->push($msg=$arr);
// retrieve data as send.
$result = $q->pop();
// same to send
$arr == $result #=> true
```

Notice, by serialize() , message size increased.(over 1024, large bytes);
```php
## large bytes 
$msg = str_rand(1024);
$q = new IPCMsgQueue('my-named-queue');
$q->push($msg);
// will be not same to $msg.
$result = $q->pop();
// to get same data,specify large size.
$result = $q->pop(null,strlen(serialize($msg)));// will be 10 byte larger.
```
I wrote this class , suppose to be used simple message. large size (>1024) is un-usual way.

## save and load when OS shutdown.

SysV IPC Message will be lost after shutdown. to prevent lost , try save and load messages.

#### Before shutdown
```php
$q = new IPCMsgQueue('my-named-queue');
// push some messages
$q->push('msg');
$q->push('msg');
$q->push('msg');
// save before shutdown.
$q->save('path/to/permanent/file');
```
#### After restart
```php
$q = new IPCMsgQueue('my-named-queue');
// load after system restarted.
$q->load('path/to/permanent/file');
// can read data 
$q->pop()#=>'msg';
$q->pop()#=>'msg';
$q->pop()#=>'msg';
```
## Multi Process (IPC)

Multi process (inter process). same name same queue.

#### consumer.php (worker)
```php
<?php
require_once 'vendor/autoload.php';
use Takuya\PhpSysvMessageQueue\IPCMsgQueue;
$name = 'my-queue';
$q = new IPCMsgQueue($name);
register_shutdown_function(fn()=>$q->destroy());
while($q->available()){
  $body = $q->pop();// blocking io
  var_dump($body);
}
```
#### producer.php ( maker )
```php
require_once 'vendor/autoload.php';
use Takuya\PhpSysvMessageQueue\IPCMsgQueue;

$name = 'my-queue';
$q = new IPCMsgQueue($name);

foreach (range(0,10) as $cnt){
  $q->push('job:'.$cnt);
  sleep(10);
}
```
#### run 
```shell
php consumer.php & # run in background.
php producer.php
```

### Comparison 

PHP's IPC System Message Queue function `msg_send` has auto serialization, This is very useful.


Compare SysV IPC Queue to another shared way. IPC Queue has easy to use.


| type | advantage to SysV mesg func  | dis-advantage to sysV func |
|:---|:---|:---|
| tcp:// | can listen another pc | manual serialization, data structure, read size needs manually adjustment  |
| Shared-Memory and  Semaphore  | can share data  |  be careful of R/W timming |
| file and flock() | data persistence is easy | Be careful of R/W timming(flock), Data structure fully considered, manual seriazation |
| sqlite / sql | Easy to data persistence | Needs SQL knowledge or O/R mapper, manuall serialization |


PHP's `SplQueue` or `SplStack` cannot share data to another process, These require sql or file library to use. 

In that reason, `msg_send msg_receive` is the easiest way to share Tasks.



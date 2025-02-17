<?php
namespace Takuya\Helpers;

if ( !function_exists('get_process_info')){
  function get_process_info($pid):array{
    $command = "ps -o ppid,pid,sid,uid,gid,tty,stat,cmd $pid";
    $output = shell_exec($command);
    $lines = explode("\n", trim($output));
    $headers = preg_split('/\s+/', array_shift($lines));
    $body = $lines[0];
    $processes =array_combine($headers, preg_split('/\s+/', ltrim($body), count($headers)));
    return $processes;
  }
  
}
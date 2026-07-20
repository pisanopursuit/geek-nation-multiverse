<?php
declare(strict_types=1);
function smtp_send(string $to, string $subject, string $html): void {
    $host=(string)config('mail.host'); $port=(int)config('mail.port',587); $user=(string)config('mail.username'); $pass=(string)config('mail.password');
    $from=(string)config('mail.from_email'); $name=(string)config('mail.from_name');
    $fp=stream_socket_client("tcp://{$host}:{$port}",$errno,$errstr,20); if(!$fp) throw new RuntimeException("SMTP connection failed: $errstr");
    stream_set_timeout($fp,20);
    $read=function()use($fp){$r='';while(($l=fgets($fp,515))!==false){$r.=$l;if(strlen($l)<4||$l[3]!=='-')break;}return $r;};
    $cmd=function(string $c,array $ok)use($fp,$read){fwrite($fp,$c."\r\n");$r=$read();$code=(int)substr($r,0,3);if(!in_array($code,$ok,true))throw new RuntimeException("SMTP error after {$c}: {$r}");};
    $read(); $cmd('EHLO geeknationmultiverse.com',[250]); $cmd('STARTTLS',[220]); if(!stream_socket_enable_crypto($fp,true,STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new RuntimeException('Unable to start TLS.');
    $cmd('EHLO geeknationmultiverse.com',[250]); $cmd('AUTH LOGIN',[334]); $cmd(base64_encode($user),[334]); $cmd(base64_encode($pass),[235]);
    $cmd('MAIL FROM:<'.$from.'>',[250]); $cmd('RCPT TO:<'.$to.'>',[250,251]); $cmd('DATA',[354]);
    $headers=["From: {$name} <{$from}>","To: <{$to}>","Subject: {$subject}",'MIME-Version: 1.0','Content-Type: text/html; charset=UTF-8'];
    $message=implode("\r\n",$headers)."\r\n\r\n".$html; $message=preg_replace('/(?m)^\./','..',$message); fwrite($fp,$message."\r\n.\r\n"); $r=$read(); if((int)substr($r,0,3)!==250) throw new RuntimeException('SMTP rejected message: '.$r); $cmd('QUIT',[221]); fclose($fp);
}

<?php

interface sessionDriverInterface{
	public function __construct($session,$options=array());
	public function isReady();
	public function open($savePath, $sessionName);
	public function close();
	public function read($sessionId);
	public function write($sessionId, $data);
	public function destroy($sessionId);
	public function gc($lifetime);
}

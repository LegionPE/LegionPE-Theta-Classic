<?php

/*
 * Theta
 *
 * Copyright (C) 2015 PEMapModder and contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PEMapModder
 */

namespace legionpe\theta\classic;

class TeleportManager{
	const DUPLICATED_REQUEST = 0;
	const MESSAGE_UPDATED = 1;
	const REQUEST_ACCEPTED = 2;
	const REQUEST_SENT = 3;
	private $main;
	private $db;
	public function __construct(ClassicPlugin $main){
		$this->main = $main;
		$this->db = new \SQLite3(":memory:");
		$this->db->exec("CREATE TABLE req (src INT, targ INT, msg TEXT, reqByFrom BOOLEAN)");
	}
	public function sendToRequest(ClassicSession $from, ClassicSession $to, $msg){
		$op = $this->db->prepare("SELECT msg,reqByFrom FROM req WHERE src=:src AND targ=:targ");
		$op->bindValue(":src", $from->getUid());
		$op->bindValue(":targ", $to->getUid());
		$result = $op->execute()->fetchArray(SQLITE3_ASSOC);
		if(is_array($result)){
			$isent = $result["reqByFrom"];
			if($isent){
				if($result["msg"] !== $msg){
					$op = $this->db->prepare("UPDATE req SET msg=:msg WHERE src=:src AND targ=:targ");
					$op->bindValue(":src", $from->getUid());
					$op->bindValue(":targ", $to->getUid());
					$op->bindValue(":msg", $msg);
					$op->execute();
					return self::MESSAGE_UPDATED;
				}
				return self::DUPLICATED_REQUEST;
			}
			$op = $this->db->prepare("DELETE FROM req WHERE src=:src AND targ=:targ");
			$op->bindValue(":src", $from->getUid());
			$op->bindValue(":targ", $to->getUid());
			$op->execute();
			return self::REQUEST_ACCEPTED;
		}
		$op = $this->db->prepare("INSERT INTO req (src,targ,msg,reqByFrom)VALUES(:src,:targ,:msg,:out)");
		$op->bindValue(":src", $from->getUid(), SQLITE3_INTEGER);
		$op->bindValue(":targ", $to->getUid(), SQLITE3_INTEGER);
		$op->bindValue(":msg", $msg, SQLITE3_TEXT);
		$op->bindValue(":out", true);
		$op->execute();
		return self::REQUEST_SENT;
	}
	public function sendHereRequest(ClassicSession $to, ClassicSession $from, $msg){
		$op = $this->db->prepare("SELECT msg,reqByFrom FROM req WHERE src=:src AND targ=:targ");
		$op->bindValue(":src", $from->getUid());
		$op->bindValue(":targ", $to->getUid());
		$result = $op->execute()->fetchArray(SQLITE3_ASSOC);
		if(is_array($result)){
			$isent = !$result["reqByFrom"];
			if($isent){
				if($result["msg"] !== $msg){
					$op = $this->db->prepare("UPDATE req SET msg=:msg WHERE src=:src AND targ=:targ");
					$op->bindValue(":src", $from->getUid());
					$op->bindValue(":targ", $to->getUid());
					$op->bindValue(":msg", $msg);
					$op->execute();
					return self::MESSAGE_UPDATED;
				}
				return self::DUPLICATED_REQUEST;
			}
			$op = $this->db->prepare("DELETE FROM req WHERE src=:src AND targ=:targ");
			$op->bindValue(":src", $from->getUid());
			$op->bindValue(":targ", $to->getUid());
			$op->execute();
			return self::REQUEST_ACCEPTED;
		}
		$op = $this->db->prepare("INSERT INTO req (src,targ,msg,reqByFrom) VALUES (:src,:targ,:msg,:in)");
		$op->bindValue(":src", $from->getUid(), SQLITE3_INTEGER);
		$op->bindValue(":targ", $to->getUid(), SQLITE3_INTEGER);
		$op->bindValue(":msg", $msg, SQLITE3_TEXT);
		$op->bindValue(":in", false);
		$op->execute();
		return self::REQUEST_SENT;
	}
}

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

use legionpe\theta\Session;

class TeleportManager{
	const SMALL_TO_BIG = 1;
	const BIG_TO_SMALL = 2;
	private $db;
	public function __construct(ClassicPlugin $main){
		$this->db = new \SQLite3(":memory:");
		$this->db->exec("CREATE TABLE tpreq (small INT, big INT, msg TEXT, state INT)");
	}
	public function sendRequest(Session $from, Session $to, $msg){
		$fu = $from->getUid();
		$tu = $to->getUid();
		$min = min($fu, $tu);
		$max = max($fu, $tu);
		$result = $this->db->query("SELECT state FROM tpreq WHERE small=$min and big=$max")->fetchArray(SQLITE3_ASSOC);
	}
}

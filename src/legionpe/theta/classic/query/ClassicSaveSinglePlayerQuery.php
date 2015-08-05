<?php

/*
 * LegionPE Theta
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

namespace legionpe\theta\classic\query;

use legionpe\theta\classic\ClassicSession;
use legionpe\theta\query\SaveSinglePlayerQuery;
use legionpe\theta\Session;

class ClassicSaveSinglePlayerQuery extends SaveSinglePlayerQuery{
	private $kills;
	public function getColumns(Session $session, $status){
		$cols = parent::getColumns($session, $status);
		if(!($session instanceof ClassicSession)){
			return $cols; // shouldn't happen
		}
		$cols["pvp_init"] = ["v" => $session->joinedClassicSince(), "noupdate" => true];
		$cols["pvp_kills"] = $this->kills = $session->getKills();
		$cols["pvp_deaths"] = $session->getDeaths();
		$cols["pvp_curstreak"] = 0;
		$cols["pvp_maxstreak"] = $session->getMaximumStreak();
		return $cols;
	}
	public function onPreQuery(\mysqli $db){
		$db->query($this->getUpdateQuery());
	}
	public function getQuery(){
		return "SELECT COUNT(*) + 1 AS rank FROM users WHERE pvp_kills > $this->kills";
	}
	public function getResultType(){
		return self::TYPE_ASSOC;
	}
}

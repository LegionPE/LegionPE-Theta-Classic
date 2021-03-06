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

use legionpe\theta\BasePlugin;
use legionpe\theta\classic\ClassicPlugin;
use legionpe\theta\classic\ClassicSession;
use legionpe\theta\classic\kit\ClassicKit;
use legionpe\theta\config\Settings;
use legionpe\theta\query\SaveSinglePlayerQuery;
use legionpe\theta\Session;
use pocketmine\Server;

class ClassicSaveSinglePlayerQuery extends SaveSinglePlayerQuery{
	private $uid;
	private $kitData;
	private $kills;
	private $userId;
	private $rank;
	public function __construct(ClassicPlugin $plugin, ClassicSession $session, $status){
		$this->uid = $session->getUid();
		$this->kitData = $session->getLoginDatum("kitData");
		parent::__construct($plugin, $session, $status);
	}
	public function getColumns(Session $session, $status){
		$this->userId = $session->getUid();
		$cols = parent::getColumns($session, $status);
		if(!($session instanceof ClassicSession)){
			return $cols; // shouldn't happen
		}
		$cols["pvp_init"] = ["v" => $session->getJoinedClassicSince(), "noupdate" => true];
		$cols["pvp_kills"] = $this->kills = $session->getKills();
		$cols["pvp_deaths"] = $session->getDeaths();
		$cols["pvp_curstreak"] = 0;
		$cols["pvp_maxstreak"] = $session->getMaximumStreak();
		if($session->currentKit instanceof ClassicKit) $cols["pvp_kit"] = $session->currentKit->id;
		return $cols;
	}
	public function onPostQuery(\mysqli $db){
		$statsPublic = Settings::CONFIG_STATS_PUBLIC;
		$result = $db->query("SELECT COUNT(*) + 1 AS rank FROM users WHERE pvp_kills > $this->kills AND (config & $statsPublic) = $statsPublic");
		$this->rank = $result->fetch_assoc()["rank"];
		$result->close();
		if(isset($this->kitData)){
			if(count($this->kitData) > 0){
				foreach($this->kitData as $kitid => $kitlevel){
					$query = $db->query("SELECT * FROM purchases_kit WHERE uid=".$this->uid." AND kitid=".$kitid);
					if($query->num_rows === 0){
						$newquery = $db->query("INSERT INTO purchases_kit VALUES ($this->uid, $kitid, $kitlevel)");
					}else{
						$newquery = $db->query("UPDATE purchases_kit SET kitlevel=".$kitlevel." WHERE uid=".$this->uid." AND kitid=".$kitid);
					}
				}
			}
		}
	}
	public function onCompletion(Server $server){
		$main = BasePlugin::getInstance($server);
		$ses = $main->getSessionByUid($this->userId);
		if($ses instanceof ClassicSession){
			$ses->setGlobalRank($this->rank);
		}
	}
}

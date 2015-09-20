<?php

/*
 * LegionPE
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
use legionpe\theta\config\Settings;
use legionpe\theta\query\AsyncQuery;
use legionpe\theta\Session;
use pocketmine\Server;

class TopKillsQuery extends AsyncQuery{
	public function __construct(BasePlugin $main, $kd, Session $sender){
		parent::__construct($main);
		$this->kd = $kd;
		$this->sender = $main->storeObject($sender);
	}
	public function getResultType(){
		return self::TYPE_ALL;
	}
	public function getQuery(){
		$statsPublic = Settings::CONFIG_STATS_PUBLIC;
		return "SELECT name,pvp_kills,(pvp_kills/users.pvp_deaths)AS kd FROM users WHERE (config & $statsPublic)=$statsPublic ORDER BY " . ($this->kd ? "kd" : "pvp_kills") . " DESC LIMIT 5";
	}
	public function getExpectedColumns(){
		return [
			"name" => self::COL_STRING,
			"pvp_kills" => self::COL_INT,
			"kd" => self::COL_FLOAT
		];
	}
	public function onCompletion(Server $server){
		$main = BasePlugin::getInstance($server);
		/** @var Session $sender */
		$sender = $main->fetchObject($this->sender);
		if($sender->getPlayer()->isOnline()){
			$result = $this->getResult();
			if($result["resulttype"] !== self::TYPE_ALL){
				return;
			}
			$max = 0;
			$rows = $result["result"];
			$i = 0;
			foreach($rows as &$row){
				$max = max(strlen($row["name"]), $max);
				$row["i"] = ++$i;
			}
			$player = $sender->getPlayer();
			array_unshift($rows, [
				"name" => "Name",
				"pvp_kills" => "Kills",
				"kd" => "K/D Ratio",
				"i" => "#"
			]);
			foreach($rows as $row){
				$player->sendMessage(
					"| " . $row["i"] . " | " . str_pad($row["name"], $max, " ", STR_PAD_BOTH) . " | " .
					str_pad($row["pvp_kills"], 6, " ", STR_PAD_BOTH) . " | " .
					str_pad($row["kd"], 9, " ", STR_PAD_BOTH) . " |"
				);
			}
		}
	}
}

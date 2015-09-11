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

namespace legionpe\theta\classic\commands;

use legionpe\theta\BasePlugin;
use legionpe\theta\classic\ClassicSession;
use legionpe\theta\command\SessionCommand;
use legionpe\theta\lang\Phrases;
use legionpe\theta\Session;

class PvpStatsCommand extends SessionCommand{
	public function __construct(BasePlugin $main){
		parent::__construct($main, "stats", "Check a player's statistics", "/stat [player name = yourself]", ["stat"]);
	}
	protected function run(array $args, Session $sender){
		/** @var ClassicSession $sender */
		if(isset($args[0])){
			$session = $this->getSession($name = array_shift($args));
			if($session === null){
				return $this->notOnline($sender, $name);
			}
		}else{
			$session = $sender;
		}
		return $sender->translate(Phrases::PVP_CMD_STATS, $this->varsFor($session));
	}
	protected function varsFor(ClassicSession $session){
		$kills = $session->getKills();
		$deaths = $session->getDeaths();
		return [
			"kills" => $kills,
			"deaths" => $deaths,
			"kd" => ($deaths === 0) ? "N/A" : round($kills / $deaths, 3),
			"rank" => $session->getGlobalRank(),
		];
	}
}

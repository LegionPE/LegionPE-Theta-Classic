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
use legionpe\theta\classic\OneVsOneMatch;
use legionpe\theta\command\SessionCommand;
use legionpe\theta\lang\Phrases;
use legionpe\theta\Session;

class OneVsOneCommand extends SessionCommand{
	public function __construct(BasePlugin $main){
		parent::__construct($main, "1v1", "Send/accept a 1v1 request", "/1v1 <player>", ["11"]);
	}
	protected function run(array $args, Session $host){
		if(!isset($args[0])){
			return false;
		}
		if(!($host instanceof ClassicSession)){
			return false;
		}
		$current = $host->getMain()->currentMatch;
		if($current !== null){
			if($current->guest !== $host){
				return $host->translate(Phrases::PVP_CMD_ONE_VS_ONE_ALREADY_OCCUPIED, [
					"other" => $current->guest === $host ? $current->host->getInGameName() : $host->getInGameName()
				]);
			}else{
				$current->onAccept();
			}
		}
		$guest = $this->getSession($name = array_shift($args));
		if(!($guest instanceof ClassicSession)){
			return $this->notOnline($host, $name);
		}
		$match = new OneVsOneMatch($host->getMain());
		$match->host = $host;
		$match->guest = $guest;
		$host->getMain()->currentMatch = $match;
		$host->send(Phrases::PVP_CMD_ONE_VS_ONE_INVITED, ["guest" => $guest->getInGameName()]);
		$guest->send(Phrases::PVP_CMD_ONE_VS_ONE_INVITED, ["host" => $host->getInGameName(), "realname" => $host->getPlayer()->getName()]);
		return true;
	}
}

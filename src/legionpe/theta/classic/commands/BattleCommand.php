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
use legionpe\theta\classic\battle\ClassicBattle;
use legionpe\theta\classic\battle\ClassicBattleKit;
use legionpe\theta\classic\ClassicPlugin;
use legionpe\theta\classic\ClassicSession;
use legionpe\theta\classic\OneVsOneMatch;
use legionpe\theta\command\SessionCommand;
use legionpe\theta\lang\Phrases;
use legionpe\theta\Session;
use pocketmine\item\Item;

class BattleCommand extends SessionCommand{
	public function __construct(BasePlugin $main){
		parent::__construct($main, "battle", "Send a battle request", "/battle <player>", ["11"]);
	}
	protected function run(array $args, Session $host){
		if(!isset($args[0])){
			return false;
		}
		if(!($host instanceof ClassicSession)){
			return false;
		}
		if($args[0] == $host->battleRequest->getPlayer()->getName()){
			if($host->battleRequest->getPlayer()->isOnline()){
				if(!($host->battleRequest->getBattle() instanceof ClassicBattle)){
					$kit = new ClassicBattleKit('Default battle kit',
						[Item::get(306), Item::get(307), Item::get(308), Item::get(309)],
						[Item::get(310), Item::get(260)],
						[]);
					new ClassicBattle($host->getMain(), [[$host], [$host->battleRequest]], 3, 90, $kit);
				}else{
					$name = $host->battleRequest->getPlayer()->getName();
					$host->battleRequest = null;
					return $name . "is already in a Battle :(";
				}
			}else{
				$name = $host->battleRequest->getPlayer()->getName();
				$host->battleRequest = null;
				return $name . " is offline :(";
			}
		}
		if(!$host->isVIP()){
			return "You have to be VIP to use this command.";
		}
		$battle = $host->getBattle();
		if($battle instanceof ClassicBattle){
			return "You're already in a Battle.";
		}
		$opponent = $host->getMain()->getSession($args[0]);
		if(!($opponent instanceof ClassicSession)){
			return $args[0] . ' is not online.';
		}
		if($opponent->getBattle() instanceof ClassicBattle){
			return $opponent->getPlayer()->getName() . ' is already in a Battle.';
		}
		$opponent->battleRequest = $host;
		$opponent->sendMessage("You have received a request from {$host->getPlayer()->getName()} to Battle.\nTo accept this request, type /battle {$host->getPlayer()->getName()}");
		$host->sendMessage("Request sent to {$opponent->getPlayer()->getName()}");
		/*$kit = new ClassicBattleKit('Default battle kit',
			[Item::get(306), Item::get(307), Item::get(308), Item::get(309)],
			[Item::get(310), Item::get(260)],
			[]);
		new ClassicBattle($host->getMain(), [[$host], [$opponent]], 3, 90, $kit);*/
		return true;
	}
}

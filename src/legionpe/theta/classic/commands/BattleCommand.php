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
use legionpe\theta\classic\ClassicSession;
use legionpe\theta\command\SessionCommand;
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
		if($host->getBattle() instanceof ClassicBattle){
			return "You're already in a Battle.";
		}
		if($host->isQueueing){
			return "You're queueing for a Battle.";
		}
		if($host->battleRequest instanceof ClassicSession){
			if($this->getSession($args[0]) instanceof ClassicSession){
				if($this->getSession($args[0]) === $host->battleRequest){
					if((time() - $host->battleLastRequest) <= 30){
						if($host->battleRequest->getPlayer()->isOnline()){
							if(!($host->battleRequest->getBattle() instanceof ClassicBattle)){
								$kit = $host->getMain()->getKits();
								shuffle($kit);
								$kit = $kit[0];
								$arena = $host->getMain()->getArenas();
								shuffle($arena);
								$arena = $arena[0];
								$battle = new ClassicBattle($host->getMain(), [[$host], [$host->battleRequest]], 3, 60, $kit, $arena);
								foreach($battle->getSessions() as $session){
									$session->battleRequestSentTo = null;
									$session->battleRequest = null;
									$session->battleLastRequest = 0;
									$session->battleLastSentRequest = 0;
								}
								return true;
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
					}else{
						$host->battleRequest = null;
						$host->battleLastRequest = 0;
						return "You've waited too long to respond to the Battle request.";
					}
				}
			}
		}
		if(!$host->isDonator()){
			return "You have to be Donator to use this command.";
		}
		$opponent = $this->getSession($args[0]);
		if(!($opponent instanceof ClassicSession)){
			return $args[0] . ' is not online.';
		}
		if($opponent === $host){
			return "You can't have a Battle with yourself. That would be pretty cool though.";
		}
		if($opponent->getBattle() instanceof ClassicBattle){
			return $opponent->getPlayer()->getName() . ' is already in a Battle.';
		}
		if((time() - $host->battleLastSentRequest) <= 20){
			return "You can't send a request again in such a short period of time.";
		}
		if($opponent === $host->battleRequestSentTo){
			if((time() - $opponent->battleLastRequest) <= 30){
				return "You can't send a request to {$opponent->getPlayer()->getName()} again in such a short period of time.";
			}
		}
		$host->battleRequestSentTo = $opponent;
		$host->battleLastSentRequest = time();
		$opponent->battleRequest = $host;
		$opponent->battleLastRequest = time();
		$opponent->sendMessage("You have received a request from {$host->getPlayer()->getName()} to Battle.\nTo accept this request, type /battle {$host->getPlayer()->getName()}");
		$host->sendMessage("Request sent to {$opponent->getPlayer()->getName()}");
		return true;
	}
}

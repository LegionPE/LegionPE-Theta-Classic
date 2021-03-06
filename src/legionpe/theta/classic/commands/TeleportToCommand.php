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

namespace legionpe\theta\classic\commands;

use legionpe\theta\BasePlugin;
use legionpe\theta\classic\ClassicConsts;
use legionpe\theta\classic\ClassicPlugin;
use legionpe\theta\classic\ClassicSession;
use legionpe\theta\classic\TeleportManager;
use legionpe\theta\command\SessionCommand;
use legionpe\theta\Friend;
use legionpe\theta\lang\Phrases;
use legionpe\theta\Session;
use pocketmine\utils\TextFormat;

class TeleportToCommand extends SessionCommand{
	public function __construct(BasePlugin $main){
		parent::__construct($main, "tp", "Send or accept request to teleport to another player", "/tp <target>", ["tpr", "tp"]);
	}
	/**
	 * @param array $args
	 * @param ClassicSession|Session $sender
	 * @return bool
	 */
	protected function run(array $args, Session $sender){
		/** @var ClassicPlugin $main */
		$main = $this->getPlugin();
		if(!isset($args[0])){
			return false;
		}
		$target = $this->getSession($name = array_shift($args));
		if(!($target instanceof ClassicSession)){
			return $this->notOnline($sender, $name);
		}
		if($sender->getMode() === Session::MODE_SPECTATING){
			if(!$sender->isVIP()){
				return TextFormat::RED . "You have to be a VIP to teleport to players while spectating.";
			}
		}
		if(ClassicConsts::isSpawn($target->getPlayer())){
			return $sender->translate(Phrases::CMD_TPR_TO_FAIL_SPAWN);
		}
		$relation = $target->getFriend($sender->getUid())->type;
		if($relation === Friend::FRIEND_BEST_FRIEND){
			if($this->proceed($sender, $target)){
				$target->send(Phrases::CMD_TPR_TO_BEST_FRIEND_TO, ["from" => $sender->getInGameName()]);
				return $sender->translate(Phrases::CMD_TPR_TO_BEST_FRIEND_FROM, ["to" => $target->getInGameName()]);
			}
		}elseif($relation === Friend::FRIEND_ENEMY){
			return $sender->translate(Phrases::CMD_TPR_TO_FAIL_ENEMY_TARGET, ["to" => $target->getInGameName()]);
		}else{
			$result = $main->getTeleportManager()->sendToRequest($sender, $target, implode(" ", $args));
			if($result === TeleportManager::DUPLICATED_REQUEST){
				return $sender->translate(Phrases::CMD_TPR_TO_FAIL_DUPLICATED, ["to" => $target->getInGameName()]);
			}elseif($result === TeleportManager::MESSAGE_UPDATED or $result === TeleportManager::REQUEST_SENT){
				$target->send(Phrases::CMD_TPR_TO_RECEIVED, ["from" => $sender->getInGameName()]);
				return $sender->translate(Phrases::CMD_TPR_TO_SENT, ["to" => $target->getInGameName()]);
			}elseif($result === TeleportManager::REQUEST_ACCEPTED){
				if($this->proceed($sender, $target)){
					$target->send(Phrases::CMD_TPR_TO_BE_ACCEPTED, ["from" => $sender->getInGameName()]);
					return $sender->translate(Phrases::CMD_TPR_TO_ACCEPTED, ["to" => $target->getInGameName()]);
				}
			}
		}
		return true;
	}
	public function proceed(Session $from, Session $to){
		foreach($this->getMain()->getSessions() as $enemy){
			if($enemy->getPlayer()->distanceSquared($to->getPlayer()) <= 25){
				if($enemy->getFriend($from->getUid()) === Friend::FRIEND_ENEMY or $enemy->getFriend($to->getUid()) === Friend::FRIEND_ENEMY){
					$from->send(Phrases::CMD_TPR_PROCEED_FAIL_ENEMY_NEARBY, [
						"from" => $from->getInGameName(),
						"to" => $to->getInGameName(),
						"enemy" => $enemy->getInGameName(),
					]);
					return false;
				}
			}
		}
		$this->getPlugin()->getServer()->getScheduler()->scheduleDelayedTask(new TeleportTask($this->getMain(), $from, $to), ClassicConsts::TELEPORT_DELAY_TICKS);
		return true;
	}
	public function checkPerm(Session $session, &$msg = null){
		if(ClassicConsts::isSpawn($session->getPlayer())){
			$msg = "You can't teleport from spawn!";
			return false;
		}
		return true;
	}
}

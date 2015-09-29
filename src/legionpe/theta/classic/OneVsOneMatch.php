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

namespace legionpe\theta\classic;

use legionpe\theta\lang\Phrases;
use legionpe\theta\utils\CallbackPluginTask;
use pocketmine\scheduler\PluginTask;

class OneVsOneMatch extends PluginTask{
	/** @var ClassicSession */
	public $host, $guest;
	const STATE_INVITING = 0;
	const STATE_PREPARING = 1;
	const STATE_FIGHTING = 2;
	const STATE_COMPLETED = 3;
	public $state = self::STATE_INVITING;
	public $creation;
	public function __construct(ClassicPlugin $main){
		parent::__construct($main);
		$this->creation = time();
		$main->getServer()->getScheduler()->scheduleDelayedTask(new CallbackPluginTask($main, function (){
			if($this->state === self::STATE_INVITING){
				$this->garbage();
			}
		}), 600);
	}
	public function onAccept(){
		$this->state = self::STATE_PREPARING;
		$this->host->getPlayer()->teleport(ClassicConsts::get1v1HostPos($this->host->getMain()->getServer()));
		$this->guest->getPlayer()->teleport(ClassicConsts::get1v1GuestPos($this->guest->getMain()->getServer()));
		$this->host->setInvincible(true);
		$this->guest->setInvincible(true);
		$this->host->setMovementBlocked(true);
		$this->guest->setMovementBlocked(true);
		$this->host->getMain()->getServer()->getScheduler()->scheduleDelayedTask($this, 200);
		$this->host->send(Phrases::PVP_CMD_ONE_VS_ONE_PREPARING, [
			"host" => $this->host->getInGameName(),
			"guest" => $this->guest->getInGameName()
		]);
		$this->guest->send(Phrases::PVP_CMD_ONE_VS_ONE_PREPARING, [
			"host" => $this->host->getInGameName(),
			"guest" => $this->guest->getInGameName()
		]);
	}
	public function onRun($t){
		$this->state = self::STATE_FIGHTING;
		$this->host->setInvincible(false);
		$this->guest->setInvincible(false);
		$this->host->setMovementBlocked(false);
		$this->guest->setMovementBlocked(false);
	}
	public function onWin(ClassicSession $winner){
		$loser = $winner === $this->host ? $this->guest : $winner;
		$winner->getPlayer()->teleport(ClassicConsts::getSpawnPosition($winner->getMain()->getServer()));
		foreach($winner->getMain()->getSessions() as $session){
			$session->send(Phrases::PVP_CMD_ONE_VS_ONE_ANNOUNCE, ["winner" => $winner->getInGameName(), "loser" => $loser->getInGameName()]);
		}
		$coins = 5;
		$points = 3;
		$winner->send(Phrases::PVP_CMD_ONE_VS_ONE_WIN_EXTRA, ["coins" => $coins, "points" => $points]);
		$winner->grantCoins($coins, true);
		$winner->grantTeamPoints($points);
		$this->garbage();
	}
	public function onQuit(ClassicSession $ses){
		$other = $ses === $this->host ? $this->guest : $ses;
		if($this->state === self::STATE_INVITING){
			$other->send(Phrases::PVP_CMD_ONE_VS_ONE_OTHER_QUIT, ["other" => $ses->getInGameName()]);
		}elseif($this->state === self::STATE_PREPARING or $this->state === self::STATE_FIGHTING){
			$other->send(Phrases::PVP_CMD_ONE_VS_ONE_OTHER_QUIT, ["other" => $ses->getInGameName()]);
			$other->getPlayer()->teleport(ClassicConsts::getSpawnPosition($ses->getMain()->getServer()));
			$this->garbage();
		}
	}
	public function garbage(){
		$this->state = self::STATE_COMPLETED;
		$this->guest->getMain()->currentMatch = null;
	}
}

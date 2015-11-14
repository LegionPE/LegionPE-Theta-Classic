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

namespace legionpe\theta\classic\battle;

use legionpe\theta\classic\ClassicPlugin;
use legionpe\theta\classic\ClassicSession;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ClassicBattle{
	CONST STATUS_STARTING = 0, STATUS_RUNNING = 1, STATUS_ENDING = 2;
	CONST PLAYER_STATUS_SPECTATING = 0, PLAYER_STATUS_PLAYING = 1;
	private static $nextId = 0;
	/** @var ClassicPlugin */
	private $plugin;
	/** @var int */
	private $id;
	/** @var ClassicBattleArena */
	private $arena;
	/** @var ClassicSession[][] */
	private $teams = [];
	/** @var int */
	private $currentRound = 0;
	/** @var int */
	private $maxRounds = 3;
	/** @var int[] */
	private $roundWinners = [];
	/** @var int */
	private $roundDuration = 90;
	/** @var int */
	private $time = 0;
	/** @var ClassicBattleKit */
	private $kit;
	/** @var int */
	private $status;
	/** @var \legionpe\theta\classic\battle\ClassicBattleOld[] */
	private $old = [];
	/** @var int */
	private $sessionTypes = [];
	/** @var bool */
	private $canHit = false;

	/**
	 * @param ClassicPlugin $plugin
	 * @param ClassicSession[][] $teams
	 * @param int $rounds
	 * @param int $duration
	 * @param ClassicBattleKit $kit
	 * @param ClassicBattleArena $arena
	 */
	public function __construct(ClassicPlugin $plugin, $teams, $rounds, $duration, ClassicBattleKit $kit, ClassicBattleArena $arena){
		$this->plugin = $plugin;
		$this->teams = $teams;
		foreach($teams as $team => $sessions){
			foreach($sessions as $session){
				$session->setBattle($this);
				$this->old[$session->getPlayer()->getName()] = new ClassicBattleOld($session);
			}
		}
		$this->maxRounds = $rounds;
		$this->roundDuration = $duration;
		$this->kit = $kit;
		$this->arena = $arena;
		$this->id = self::$nextId++;
		$plugin->battles[$this->id] = $this;
		$this->setStatus(self::STATUS_STARTING, TextFormat::GOLD . "Battle starting..");

		// spawn 1: 212 16 22 yaw 150
		// spawn 2: 200 16 3 yaw -30
	}
	/**
	 * @return int
	 */
	public function getId(){
		return $this->id;
	}
	/**
	 * @return \legionpe\theta\classic\ClassicSession[][]
	 */
	public function getTeam(){
		return $this->teams;
	}
	/**
	 * @return int
	 */
	public function getRound(){
		return $this->currentRound;
	}
	/**
	 * @param $round
	 */
	public function setRound($round){
		$this->currentRound = $round;
	}
	/**
	 * @return int
	 */
	public function getMaxRounds(){
		return $this->maxRounds;
	}
	/**
	 * @param ClassicSession $session
	 */
	public function addRoundWinner(ClassicSession $session){
		$this->roundWinners[] = $this->getSessionTeam($session);
	}
	/**
	 * @return int
	 */
	public function getWinningTeam(){
		$temp = [];
		foreach($this->roundWinners as $winningTeam){
			if(isset($temp[$winningTeam])){
				++$temp[$winningTeam];
			}else{
				$temp[$winningTeam] = 1;
			}
		}
		arsort($temp);
		return key($temp);
	}
	/**
	 * @return string
	 */
	public function getOverallWinner(){
		$winningTeam = $this->getWinningTeam();
		$winners = [];
		foreach($this->teams as $team => $sessions){
			if($team === $winningTeam){ // if the team is the winning team
				foreach($sessions as $session){
					$winners[] = $session->getPlayer()->getName(); // add all the players from that team to $winners
				}
			}
		}
		return implode(", ", $winners);
	}
	/**
	 * @param ClassicSession $session
	 * @return int
	 */
	public function getRoundsWon(ClassicSession $session){
		$wins = 0;
		$sessionTeam = $this->getSessionTeam($session);
		foreach($this->roundWinners as $winningTeam){
			if($sessionTeam === $winningTeam){ // if the team from that round was the session's team, add one to $wins
				++$wins;
			}
		}
		return $wins;
	}
	/**
	 * @param ClassicSession $session
	 * @return int
	 */
	public function getKills(ClassicSession $session){
		return $this->kills[$session->getPlayer()->getName()];
	}
	/**
	 * @param ClassicSession $session
	 * @param int $kills
	 */
	public function setKills(ClassicSession $session, $kills){
		$this->kills[$session->getPlayer()->getName()] = $kills;
	}
	/**
	 * @return int
	 */
	public function getTime(){
		return $this->time;
	}
	/**
	 * @param int $time
	 */
	public function setTime($time){
		$this->time = $time;
	}
	/**
	 * @return int
	 */
	public function getDuration(){
		return $this->roundDuration;
	}
	/**
	 * @param ClassicSession $session
	 * @return int
	 */
	public function getSessionType(ClassicSession $session){
		return $this->sessionTypes[$session->getPlayer()->getName()];
	}
	/**
	 * @param ClassicSession $session
	 * @param int $type
	 * @return mixed
	 */
	public function setSessionType(ClassicSession $session, $type){
		if($type === self::PLAYER_STATUS_SPECTATING){
			$session->getPlayer()->setGamemode(1);
			foreach($this->getSessions() as $newSession){
				$newSession->getPlayer()->getPlayer()->hidePlayer($session->getPlayer());
			}
		}
		$this->sessionTypes[$session->getPlayer()->getName()] = $type;
	}
	/**
	 * @return int
	 */
	public function getStatus(){
		return $this->status;
	}
	/**
	 * @param int $status
	 * @param string $message
	 * @param string $winner
	 */
	public function setStatus($status, $message = "", $winner = "no one"){
		switch($status){
			case self::STATUS_STARTING:
				++$this->currentRound;
				foreach($this->teams as $team => $sessions){
					foreach($sessions as $index => $session){
						if(!$session->getPlayer()->isOnline()){ // battle might of been constructed just before BattleTask and there might be a case when the player leaves the battle in that time. if so, stop the battle
							$this->setStatus(self::STATUS_ENDING, "Error: player left before Battle could start");
							break;
						}
						$this->arena->teleportToSpawnpoint($team, $index, $session);
						$this->kit->apply($session);
						$this->hideOnlinePlayers($session);
						$this->setSessionType($session, self::PLAYER_STATUS_PLAYING);
						foreach($this->getSessions() as $newSession){ // send custom nametags
							$nameTag = $this->getSessionTeam($newSession) === $team ? TextFormat::GREEN . $session->getPlayer()->getName() : TextFormat::RED . $session->getPlayer()->getName();
							$session->getPlayer()->sendData($newSession->getPlayer(), [Player::DATA_NAMETAG => [Player::DATA_TYPE_STRING, $nameTag]]);
						}
						if($message !== ""){
							$session->sendMessage($message);
						}
					}
					$this->time = 5;
				}
				$this->canHit = false;
				break;
			case self::STATUS_RUNNING:
				foreach($this->getSessions() as $session){
					if($message !== ""){
						$session->sendMessage($message);
					}
					$session->sendMessage(TextFormat::GOLD . "Round " . TextFormat::RED . $this->getRound() . "/" . $this->getMaxRounds());
				}
				$this->time = $this->roundDuration;
				$this->canHit = true;
				break;
			case self::STATUS_ENDING:
				foreach($this->getSessions() as $session){
					if($session->getPlayer()->isOnline()){ // check if player online, because maybe the battle stopped because a player left
						$this->old[$session->getPlayer()->getName()]->restore();
						$session->setBattle(null);
						$coins = ($this->getWinningTeam() === $this->getSessionTeam($session) ? 34 : $this->getRoundsWon($session) * 6);
						$session->grantCoins($coins, false, false);
						$session->sendMessage(TextFormat::GOLD . "You won " . TextFormat::RED . $this->getRoundsWon($session) . TextFormat::GOLD . " rounds and received " . TextFormat::RED . $coins . TextFormat::GOLD . " coins.\n" . TextFormat::GOLD . "Winner: " . TextFormat::RED . $winner);
						if($message !== ""){
							$session->sendMessage($message);
						}
					}
					foreach($this->plugin->getServer()->getOnlinePlayers() as $player){ // force show all players, normally wouldn't do this but too many bugs
						$player->showPlayer($session->getPlayer());
						$session->getPlayer()->showPlayer($player);
					}
				}
				$this->canHit = false;
				unset($this->plugin->battles[$this->id]);
				break;
		}
		$this->status = $status;
	}
	/**
	 * @return ClassicBattleKit
	 */
	public function getKit(){
		return $this->kit;
	}
	/**
	 * @return \legionpe\theta\classic\ClassicSession[][]
	 */
	public function getTeams(){
		return $this->teams;
	}
	/**
	 * @return bool
	 */
	public function canHit(){
		return $this->canHit;
	}
	/**
	 * @return ClassicSession[]
	 */
	public function getSessions(){
		$out = [];
		foreach($this->teams as $team => $sessions){
			foreach($sessions as $session){
				$out[] = $session;
			}
		}
		return $out;
	}
	/**
	 * @param ClassicSession $session
	 * @return bool|int
	 */
	public function getSessionTeam(ClassicSession $session){
		$returnTeam = false;
		foreach($this->teams as $team => $sessions){
			foreach($sessions as $newSession){
				if($session === $newSession){
					$returnTeam = $team;
				}
			}
		}
		return $returnTeam;
	}
	/**
	 * @param ClassicSession $session
	 */
	private function hideOnlinePlayers(ClassicSession $session){
		foreach($this->plugin->getServer()->getOnlinePlayers() as $onlinePlayer){
			$onlinePlayer->hidePlayer($session->getPlayer());
			$session->getPlayer()->hidePlayer($onlinePlayer);
		}
		foreach($this->getSessions() as $battleSession){
			foreach($this->getSessions() as $newBattleSession){
				$battleSession->getPlayer()->showPlayer($newBattleSession->getPlayer());
				$newBattleSession->getPlayer()->showPlayer($battleSession->getPlayer());
			}
		}
	}
	/**
	 * @param string $message
	 * @param string $type
	 */
	public function broadcast($message, $type = "msg"){
		switch($type){
			case "msg":
				foreach($this->getSessions() as $session){
					$session->getPlayer()->sendMessage($message);
				}
				break;
			case "popup":
				foreach($this->getSessions() as $session){
					$session->getPlayer()->sendPopup($message);
				}
				break;
			case "tip":
				foreach($this->getSessions() as $session){
					$session->getPlayer()->sendTip($message);
				}
				break;
		}
	}

}

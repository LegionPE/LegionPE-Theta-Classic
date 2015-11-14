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

namespace legionpe\theta\classic\battle\queue;

use legionpe\theta\classic\battle\ClassicBattleKit;
use legionpe\theta\classic\ClassicSession;

class ClassicBattleQueue{
	CONST TYPE_FIXED = 0, TYPE_RANDOM = 1;
	private static $qId = 0;
	/** @var int */
	private $id;
	/** @var bool|\legionpe\theta\classic\battle\ClassicBattleKit */
	private $kit;
	/** @var int */
	private $kitType;
	/** @var bool|\legionpe\theta\classic\battle\ClassicBattleArena */
	private $arena;
	/** @var int */
	private $arenaType;
	/** @var int */
	private $playersPerTeam = 1;
	/** @var \legionpe\theta\classic\ClassicSession */
	private $session;

	/**
	 * @param \legionpe\theta\classic\battle\queue\QueueManager;
	 * @param \legionpe\theta\classic\ClassicSession $session
	 * @param bool|\legionpe\theta\classic\battle\ClassicBattleKit $kit
	 * @param bool|\legionpe\theta\classic\battle\ClassicBattleArena $arena
	 * @param int $playersPerTeam
	 */
	public function __construct(QueueManager $manager, ClassicSession $session, $kit, $arena, $playersPerTeam){
		$this->session = $session;
		$this->kit = $kit;
		$this->kitType = $kit === false ? self::TYPE_RANDOM : self::TYPE_FIXED;
		$this->arena = $arena;
		$this->arenaType = $arena === false ? self::TYPE_RANDOM : self::TYPE_FIXED;
		$this->playersPerTeam = $playersPerTeam;
		if(count($manager->getQueues()) !== 0){ // set id, will tidy this up later
			foreach($manager->getQueues() as $queue){
				if($queue->getArenaType() === $this->arenaType and $queue->getKitType() === $this->kitType and $queue->getPlayersPerTeam() === $this->playersPerTeam){
					$this->id = $queue->getId();
					break;
				}else{
					$this->id = self::$qId++;
				}
			}
		}else{
			$this->id = self::$qId++;
		}
	}
	/**
	 * @return ClassicSession
	 */
	public function getSession(){
		return $this->session;
	}

	/**
	 * @return int
	 */
	public function getId(){
		return $this->id;
	}
	/**
	 * @return int
	 */
	public function getKitType(){
		return $this->kitType;
	}
	/**
	 * @return bool|\legionpe\theta\classic\battle\ClassicBattleKit
	 */
	public function getKit(){
		return $this->kit;
	}
	/**
	 * @return int
	 */
	public function getArenaType(){
		return $this->arenaType;
	}
	/**
	 * @return bool|\legionpe\theta\classic\battle\ClassicBattleArena
	 */
	public function getArena(){
		return $this->arena;
	}
	/**
	 * @return int
	 */
	public function getPlayersPerTeam(){
		return $this->playersPerTeam;
	}

}

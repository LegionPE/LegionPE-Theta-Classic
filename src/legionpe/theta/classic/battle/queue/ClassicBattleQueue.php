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

use legionpe\theta\classic\ClassicSession;

class ClassicBattleQueue{
	CONST TYPE_FIXED = 0, TYPE_RANDOM = 1;
	/** @var bool|\legionpe\theta\classic\battle\ClassicBattleKit */
	private $kit;
	/** @var int */
	private $kitType;
	/** @var int */
	private $playersPerTeam = 1;
	/** @var \legionpe\theta\classic\ClassicSession */
	private $session;

	/**
	 * @param ClassicSession $session
	 * @param bool|\legionpe\theta\classic\battle\ClassicBattleKit $kit
	 * @param int $playersPerTeam
	 */
	public function __construct(ClassicSession $session, $kit, $playersPerTeam){
		$this->session = $session;
		$this->kit = $kit;
		$this->kitType = $kit === false ? self::TYPE_RANDOM : self::TYPE_FIXED;
		$this->playersPerTeam = $playersPerTeam;
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
	public function getPlayersPerTeam(){
		return $this->playersPerTeam;
	}

}

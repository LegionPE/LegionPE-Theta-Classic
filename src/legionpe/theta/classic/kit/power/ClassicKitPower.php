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

namespace legionpe\theta\classic\kit\power;

use legionpe\theta\classic\ClassicSession;

abstract class ClassicKitPower{
	public static $nId = 0;
	/** @var string */
	protected $name;
	/** @var int */
	protected $level;
	/** @var string */
	protected $description;
	/** @var int */
	protected $delay;
	/** @var int */
	protected $lastActivate = 0;
	/** @var int */
	protected $duration = 10;

	public $id = 0;
	public $isPermanent = false;
	public $isActive = false;

	/**
	 * @param $name
	 */
	protected function setName($name){
		$this->name = $name;
	}
	/**
	 * @return string
	 */
	public function getName(){
		return $this->name;
	}
	/**
	 * @param $level
	 */
	protected function setLevel($level){
		$this->level = $level;
	}
	/**
	 * @return int
	 */
	public function getLevel(){
		return $this->level;
	}
	/**
	 * @param string $description
	 */
	protected function setDescription($description){
		$this->description = $description;
	}
	/**
	 * @return string
	 */
	public function getDescription(){
		return $this->description;
	}
	/**
	 * @return int
	 */
	public function getDelay(){
		return $this->delay;
	}
	/**
	 * @return int
	 */
	public function getDuration(){
		return $this->duration;
	}
	/**
	 * @return int
	 */
	public function getTimeTillNextActivate(){
		return $this->delay - (time() - $this->lastActivate);
	}
	/**
	 * @return bool
	 */
	public function canSetActive(){
		return $this->getTimeTillNextActivate() < 0 ? true : false;
	}
	/**
	 * @return int
	 */
	public function getTimeActive(){
		return time() - $this->lastActivate;
	}
	/**
	 * @param $bool
	 */
	public function setActive($bool){
		if($bool){
			$this->isActive = true;
			$this->lastActivate = time();
		}else{
			$this->isActive = false;
			$this->lastActivate = time();
		}
	}
	/**
	 * @return bool
	 */
	public function isActive(){
		if($this->isPermanent) return true;
		if($this->isActive){
			if(time() - $this->lastActivate <= $this->duration){
				return true;
			}else{
				$this->lastActivate = time();
				return $this->isActive = false;
			}
		}
		return false;
	}

	public abstract function onGeneral(ClassicSession $session);
	public abstract function onDamageByEntity(ClassicSession $damager, ClassicSession $damaged, &$damage);
	public abstract function onDamage(ClassicSession $session, &$damage, $event);
	public abstract function onAttack(ClassicSession $attacker, ClassicSession $victim, &$damage);
	public abstract function onHeal(ClassicSession $owner, &$health);
	public abstract function onMove(ClassicSession $session);
}

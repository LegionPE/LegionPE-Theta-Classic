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
use pocketmine\item\Item;

class StrengthPower extends ClassicKitPower{
	/** @var \pocketmine\item\Item */
	public $item;
	public function __construct($name, $description, $level, Item $item){
		$this->setName($name);
		$this->setDescription($description);
		$this->setLevel($level);
		$this->item = $item;
		switch($level){
			case 1:
				$this->delay = 120;
				$this->duration = 10;
				break;
			case 2:
				$this->delay = 120;
				$this->duration = 15;
				break;
			case 3:
				$this->delay = 90;
				$this->duration = 15;
				break;
			case 4:
				$this->delay = 90;
				$this->duration = 15;
				break;
		}
	}
	public function onGeneral(ClassicSession $session){

	}
	public function onDamageByEntity(ClassicSession $damager, ClassicSession $damaged, &$damage){

	}
	public function onDamage(ClassicSession $session, &$damage, $event){

	}
	public function onAttack(ClassicSession $attacker, ClassicSession $victim, &$damage){
		if($this->isActive()){
			$damage += $this->getLevel() / 2;
		}
	}
	public function onHeal(ClassicSession $owner, &$health){

	}
}

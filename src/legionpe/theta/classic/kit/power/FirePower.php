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

class FirePower extends ClassicKitPower{
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
				$this->duration = 20;
				break;
			case 2:
				$this->delay = 90;
				$this->duration = 20;
				break;
			case 3:
				$this->delay = 90;
				$this->duration = 30;
				break;
			case 4:
				$this->delay = 90;
				$this->duration = 30;
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
			$victim->getPlayer()->setOnFire($this->getLevel());
		}
	}
	public function onHeal(ClassicSession $owner, &$health){

	}
	public function onMove(ClassicSession $session){

	}
}

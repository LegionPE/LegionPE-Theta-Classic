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
use pocketmine\event\entity\EntityDamageEvent;

class NoFireDamagePower extends ClassicKitPower{
	public function __construct($name, $description, $level){
		$this->setName($name);
		$this->setDescription($description);
		$this->setLevel($level);
		$this->isPermanent = true;
	}
	public function onGeneral(ClassicSession $session){

	}
	public function onDamageByEntity(ClassicSession $damaged, ClassicSession $damager, &$damage){

	}
	public function onDamage(ClassicSession $session, &$damage, $event){
		if($event instanceof EntityDamageEvent){
			if($event->getCause() === EntityDamageEvent::CAUSE_FIRE || $event->getCause() === EntityDamageEvent::CAUSE_FIRE_TICK){
				$damage = 0;
			}
		}
	}
	public function onAttack(ClassicSession $attacker, ClassicSession $victim, &$damage){

	}
	public function onHeal(ClassicSession $owner, &$health){

	}
	public function onMove(ClassicSession $session){

	}
}

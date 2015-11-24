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

class StrengthPower extends ClassicKitPower{
	public function __construct($name){
		$this->setName($name);
	}
	public function onDamage(ClassicSession $damager, ClassicSession $damaged, &$damage){

	}
	public function onAttack(ClassicSession $attacker, ClassicSession $victim, &$damage){
		$victim->getPlayer()->setOnFire($this->getLevel() * 2);
	}
	public function onHeal(ClassicSession $owner, &$health){

	}
}

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

namespace legionpe\theta\classic;

use pocketmine\entity\Effect;
use pocketmine\scheduler\PluginTask;

class PostRespawnTask extends PluginTask{
	/** @var ClassicSession */
	private $session;
	public function __construct(ClassicPlugin $main, ClassicSession $session){
		$this->session = $session;
		parent::__construct($main);
	}
	public function onRun($currentTick){
		$spawn = ClassicConsts::getSpawnPosition($this->getOwner()->getServer());
		$this->session->getPlayer()->teleport($spawn);
		$this->session->equip();
		$this->session->getPlayer()->addEffect(Effect::getEffect(Effect::HEALTH_BOOST)->setDuration(0x7FFFFF)->setVisible(false)->setAmplifier(9));
	}
}

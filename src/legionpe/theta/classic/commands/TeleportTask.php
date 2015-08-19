<?php

/*
 * Theta
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

namespace legionpe\theta\classic\commands;

use legionpe\theta\BasePlugin;
use legionpe\theta\classic\ClassicPlugin;
use legionpe\theta\classic\ClassicSession;
use legionpe\theta\lang\Phrases;
use legionpe\theta\Session;
use pocketmine\scheduler\PluginTask;

class TeleportTask extends PluginTask{
	private $fromUid, $toUid;
	private $fromName, $toName;
	public function __construct(BasePlugin $main, Session $from, Session $to){
		parent::__construct($main);
		$this->fromUid = $from->getUid();
		$this->toUid = $to->getUid();
		$this->fromName = $from->getInGameName();
		$this->toName = $to->getInGameName();
	}
	public function onRun($currentTick){
		/** @var ClassicPlugin $main */
		$main = $this->getOwner();
		$from = $main->getSessionByUid($this->fromUid);
		$to = $main->getSessionByUid($this->toUid);
		if(!($from instanceof ClassicSession)){
			$to->send(Phrases::CMD_TPR_PROCEED_FAIL_OFFLINE, ["name" => $this->fromName]);
			return;
		}
		if(!($to instanceof ClassicSession)){
			$from->send(Phrases::CMD_TPR_PROCEED_FAIL_OFFLINE, ["name" => $this->toName]);
			return;
		}
	}
}

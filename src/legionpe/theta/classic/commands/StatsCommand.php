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

namespace legionpe\theta\classic\commands;

use legionpe\theta\BasePlugin;
use legionpe\theta\classic\ClassicConsts;
use legionpe\theta\command\SessionCommand;
use legionpe\theta\Session;

class StatsCommand extends SessionCommand{
	public function __construct(BasePlugin $main){
		parent::__construct($main, "stats", "View someones stats", "/stats");
	}
	protected function run(array $args, Session $sender){
		return "§2Kills: §c" . ($kills = $sender->getKills()) . "\n§2Deaths: §c" . ($deaths = $sender->getDeaths()) . "\n§2K/D ratio: §c" . ($deaths === 0 ? $kills : round($kills / $deaths, 3)) . "\n§2Next tag: §c" . ClassicConsts::getNextKillsTag($kills);
	}
}

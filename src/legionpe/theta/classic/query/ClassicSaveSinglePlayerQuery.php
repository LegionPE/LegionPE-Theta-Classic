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

namespace legionpe\theta\classic\query;

use legionpe\theta\classic\ClassicSession;
use legionpe\theta\query\SaveSinglePlayerQuery;
use legionpe\theta\Session;

class ClassicSaveSinglePlayerQuery extends SaveSinglePlayerQuery{
	public function getColumns(Session $session, $status){
		$cols = parent::getColumns($session, $status);
		if(!($session instanceof ClassicSession)){
			return $cols; // shouldn't happen
		}
		$cols["pvp_init"] = ["v" => $session->joinedClassicSince(), "noupdate" => true];
		$cols["pvp_kills"] = $session->getKills();
		$cols["pvp_deaths"] = $session->getDeaths();
		$cols["pvp_curstreak"] = $session->getCurrentStreak();
		$cols["pvp_maxstreak"] = $session->getMaximumStreak();
		return $cols;
	}
}

<?php

/**
 * Theta
 * Copyright (C) 2015 PEMapModder
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace legionpe\theta\classic\query;

use legionpe\theta\query\LoginDataQuery;

class ClassicLoginDataQuery extends LoginDataQuery{
	public function getQuery(){
		$query = parent::getQuery();
		return "SELECT pvp_init,pvp_kills,pvp_deaths,pvp_curstreak,pvp_maxstreak," . substr($query, 7);
	}
	public function getExpectedColumns(){
		$r = parent::getExpectedColumns();
		$r["pvp_init"] = self::COL_UNIXTIME;
		$r["pvp_kills"] = self::COL_INT;
		$r["pvp_deaths"] = self::COL_INT;
		$r["pvp_curstreak"] = self::COL_INT;
		$r["pvp_maxstreak"] = self::COL_INT;
		return $r;
	}
}

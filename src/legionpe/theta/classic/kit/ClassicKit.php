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

namespace legionpe\theta\classic\kit;

use legionpe\theta\classic\ClassicSession;

abstract class ClassicKit{
	/** @var string */
	protected $name;
	/** @var string */
	protected $description;
	/** @var power\ClassicKitPower[] */
	protected $powers = [];
	/** @var \pocketmine\item\Item[] */
	protected $items = [];
	public abstract function equip(ClassicSession $session);

	/**
	 * @param string $name
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
	 * @param \pocketmine\item\Item[] $items
	 */
	protected function setItems($items){
		$this->items = $items;
	}
	/**
	 * @return \pocketmine\item\Item[]
	 */
	protected function getItems(){
		return $this->items;
	}
	/**
	 * @param power\ClassicKitPower[] $powers
	 */
	protected function setPowers($powers){
		$this->powers = $powers;
	}
	/**
	 * @return power\ClassicKitPower[]
	 */
	public function getPowers(){
		return $this->powers;
	}
}

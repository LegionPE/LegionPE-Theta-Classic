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

use legionpe\theta\BasePlugin;
use legionpe\theta\classic\battle\ClassicBattle;
use legionpe\theta\classic\battle\ClassicBattleKit;
use legionpe\theta\classic\battle\queue\ClassicBattleQueue;
use legionpe\theta\classic\kit\ClassicKit;
use legionpe\theta\classic\kit\DefaultKit;
use legionpe\theta\classic\kit\FrozoneKit;
use legionpe\theta\classic\kit\KnightKit;
use legionpe\theta\classic\kit\PyroKit;
use legionpe\theta\classic\shop\CurrentKitStand;
use legionpe\theta\classic\shop\KitStand;
use legionpe\theta\Friend;
use legionpe\theta\lang\Phrases;
use legionpe\theta\query\SetFriendQuery;
use legionpe\theta\Session;
use legionpe\theta\utils\MUtils;
use legionpe\theta\utils\SpawnGhastParticle;
use pocketmine\block\Block;
use pocketmine\entity\Arrow;
use pocketmine\level\Position;
use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\entity\Effect;
use pocketmine\entity\Egg;
use pocketmine\entity\Projectile;
use pocketmine\entity\Snowball;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\inventory\InventoryPickupArrowEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\inventory\ChestInventory;
use pocketmine\item\Bow;
use pocketmine\item\ChainChestplate;
use pocketmine\item\DiamondSword;
use pocketmine\item\GoldBoots;
use pocketmine\item\GoldHelmet;
use pocketmine\item\Item;
use pocketmine\item\LeatherPants;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

const ENABLE_HEALTHBAR = true;

class ClassicSession extends Session{
	/** @var BasePlugin */
	private $main;
	/** @var ClassicBattle|null */
	private $battle = null;
	/** @var ClassicSession|null */
	public $battleRequest = null, $battleRequestSentTo = null;
	/** @var int */
	public $battleLastRequest = 0, $battleLastSentRequest = 0;
	/** @var kit\ClassicKit|null */
	public $currentKit = null;
	public $kitData = [];
	/** @var int */
	public $timesHitWithoutResponse = 0;
	/** @var bool */
	public $isQueueing = false;
	/** @var bool */
	public $hasEquipped = false;
	/** @var int */
	private $globalRank = 0;
	/** @var bool */
	private $friendlyFireActivated;
	/** @var float */
	public $lastHurtTime = 0.0, $nextCooldownTimeout = 0.0;
	/** @var Block|null */
	private $lastDamageBlock = null;
	/** @var float */
	private $lastKillTime = 0.0, $nextKillstreakTimeout = ClassicConsts::KILLSTREAK_TIMEOUT_BASE, $lastDamageTime = 0.0;
	private $lastRespawnTime;
	/** @var EntityDamageByEntityEvent|null */
	private $lastFallLavaCause = null;
	/** @var shop\KitStand[] */
	public $kitStands = [];
	/** @var shop\CurrentKitStand */
	public $currentKitStand;
	private $counter = 0;
	private $invincible = false, $movementBlocked = false;
	private $combatModeExpiry = 0;
	public function __construct(BasePlugin $main, Player $player, array $loginData){
		$this->main = $main;
		parent::__construct($player, $loginData);
		if(!$this->getLoginDatum("pvp_init")){
			$this->setLoginDatum("pvp_init", time());
		}
		if($main instanceof ClassicPlugin){
			foreach($main->getBattles() as $battle){
				foreach($battle->getSessions() as $session){
					$session->getPlayer()->hidePlayer($this->getPlayer());
				}
			}
		}
	}
	public function setCurrentKit(ClassicKit $kit){
		$this->currentKit = $kit;
		$this->setLoginDatum('pvp_kit', $kit->id);
	}
	public function setCurrentKitById($id, $level){
		$class = ClassicKit::getKitClassById($id);
		switch($class){
			case FrozoneKit::class:
				if($this->main instanceof ClassicPlugin){
					$this->currentKit = new $class($level, $this->main->resetBlocksTask);
				}
				break;
			default:
				$this->currentKit = new $class($level);
		}
		$this->setLoginDatum('pvp_kit', $this->currentKit->id);
	}
	public function getKitLevel(ClassicKit $kit){
		$kitData = $this->getLoginDatum('kitData');
		return isset($kitData[$kit->id]) ? (int) $kitData[$kit->id] : ($kit->id === ClassicKit::KIT_ID_DEFAULT ? 1 : 0);
	}
	public function getKitLevelById($id){
		$kitData = $this->getLoginDatum('kitData');
		return isset($kitData[$id]) ? (int) $kitData[$id] : ($id === ClassicKit::KIT_ID_DEFAULT ? 1 : 0);
	}
	public function setKitLevel(ClassicKit $kit, $level){
		$kitData = $this->getLoginDatum('kitData');
		$kitData[$kit->id] = $level;
		$this->setLoginDatum('kitData', $kitData);
	}
	public function setKitLevelById($id, $level){
		$kitData = $this->getLoginDatum('kitData');
		$kitData[$id] = $level;
		$this->setLoginDatum('kitData', $kitData);
	}
	public function onTeleport(EntityTeleportEvent $event){
		if($this->battle instanceof ClassicBattle){
			if($this->battle->getRound() !== 1 and $this->battle->getStatus() === ClassicBattle::STATUS_STARTING){
				return false;
			}
		}
	}

	public function onDamage(EntityDamageEvent $event){
		if(!parent::onDamage($event)){
			return false;
		}
		if($this->invincible){
			return false;
		}
		if($event->getCause() === EntityDamageEvent::CAUSE_FALL){
			return false;
		}
		if($event->getCause() === EntityDamageEvent::CAUSE_SUICIDE){
			return true;
		}
		if(ClassicConsts::isSpawn($this->getPlayer())){
			return false;
		}
		$damage = $event->getDamage();
		if($this->currentKit instanceof ClassicKit){
			foreach($this->currentKit->getPowers() as $power){
				$power->onDamage($this, $damage, $event);
			}
		}
		if($event instanceof EntityDamageByEntityEvent){
			$fromEnt = $event->getDamager();
			if($fromEnt instanceof Player){
				$ses = $this->getMain()->getSession($fromEnt);
				if(ClassicConsts::isSpawn($fromEnt)){
					if($ses instanceof ClassicSession){
						$fromEnt->sendTip($ses->translate(Phrases::PVP_ATTACK_SPAWN));
					}
					return false;
				}
				if($ses instanceof ClassicSession){
					if($ses->getBattle() instanceof ClassicBattle and $this->getBattle() instanceof ClassicBattle){
						if($ses->getBattle() === $this->getBattle()){
							if($this->getBattle()->getSessionType($ses) !== ClassicBattle::PLAYER_STATUS_SPECTATING){
								if($this->getBattle()->getSessionType($this) !== ClassicBattle::PLAYER_STATUS_SPECTATING){
									if($this->getBattle()->getSessionTeam($this) !== $ses->getBattle()->getSessionTeam($ses)){
										if($event->getDamage() >= $this->getPlayer()->getHealth()){
											$sessionCount = count($this->getBattle()->getSessions());
											$ses->sendMessage(TextFormat::GOLD . "You killed " . TextFormat::RED . $this->getPlayer()->getName());
											$this->sendMessage(TextFormat::RED . $ses->getPlayer()->getName() . TextFormat::GOLD . " killed you with " . TextFormat::RED . ($this->getPlayer()->getHealth() / 2) . " hearts");
											$event->setDamage(0);
											if($sessionCount === 2){ // if the battle is a 1v1
												$this->getBattle()->addRoundWinner($ses);
												if($this->getBattle()->getRound() === $this->getBattle()->getMaxRounds()){
													$this->getBattle()->setStatus(ClassicBattle::STATUS_ENDING, TextFormat::GOLD . TextFormat::GOLD . TextFormat::GOLD . "The Battle has ended.", $this->getBattle()->getOverallWinner());
												}else{
													$this->getBattle()->setStatus(ClassicBattle::STATUS_STARTING, TextFormat::GOLD . "Round winner: " . TextFormat::RED . $ses->getPlayer()->getName());
												}
											}else{
												$this->getBattle()->setSessionType($this, ClassicBattle::PLAYER_STATUS_SPECTATING); // set the killed player to spectator mode
												$spectatingPlayersCount = 0;
												$winners = [];
												$team = $this->getBattle()->getSessionTeam($this);
												foreach($this->getBattle()->getTeams() as $killedTeam => $newSessions){
													foreach($newSessions as $newSession){ // newSession = a session from the battle
														if($team === $killedTeam){
															if($this->getBattle()->getSessionType($newSession) === ClassicBattle::PLAYER_STATUS_SPECTATING){
																$spectatingPlayersCount++;
																if($newSession !== $this) $newSession->sendMessage(TextFormat::GREEN . $this->getPlayer()->getName() . TextFormat::GOLD . " was killed by " . TextFormat::RED . $ses->getPlayer()->getName());
																/*
	                                                              if the newSession team was equal to the team that the killed player was in and the newSession was in spectator mode, add one to the spectatingPlayersCount
	                                                            */
															}
														}else{
															$winners[] = $newSession->getPlayer()->getName();
															$newSession->sendMessage(TextFormat::GREEN . $ses->getPlayer()->getName() . TextFormat::GOLD . " killed " . TextFormat::RED . $this->getPlayer()->getName());
															// or add the player to the winners, so if this is the last round then we know who the winners are
														}
													}
												}
												if($spectatingPlayersCount === $sessionCount / 2){ // if the amount of spectating players (from the killed player team) is equal to the amount of players in one team
													$this->getBattle()->addRoundWinner($ses);
													if($this->getBattle()->getRound() === $this->getBattle()->getMaxRounds()){ // battle ends
														$this->getBattle()->setStatus(ClassicBattle::STATUS_ENDING, TextFormat::GOLD . TextFormat::GOLD . "The Battle has ended.", $this->getBattle()->getOverallWinner());
													}else{
														$this->getBattle()->setStatus(ClassicBattle::STATUS_STARTING, TextFormat::GOLD . "Round winner: " . TextFormat::RED . implode(", ", $winners));
													}
												}
											}
										}
										return true;
									}
								}
							}
							return false;
						}
					}
					$this->setCombatMode();
					$ses->setCombatMode();
					if($ses->isInvincible()){
						$fromEnt->sendTip($ses->translate(Phrases::PVP_ATTACK_SPAWN));
						return false;
					}
					if($this->currentKit instanceof ClassicKit){
						foreach($this->currentKit->getPowers() as $power){
							$power->onDamage($this, $ses, $damage);
						}
					}
					if($ses->currentKit instanceof ClassicKit){
						foreach($ses->currentKit->getPowers() as $power){
							$power->onAttack($ses, $this, $damage);
						}
					}
//					$type = $this->getFriendType($ses->getUid());
//					if($type >= self::FRIEND_LEVEL_GOOD_FRIEND and !$this->isFriendlyFire() and !$ses->isFriendlyFire()){
//						$fromEnt->sendTip($this->translate(Phrases::PVP_ATTACK_FRIENDS, [
//							"target" => $this->getInGameName(),
//							"type" => $this->translate(self::$FRIEND_TYPES[$type])
//						]));
//						return false;
//					}
					$type = $this->getFriend($ses->getUid())->type;
					if($type >= Friend::FRIEND_GOOD_FRIEND){
						if(!$this->isFriendlyFireActivated() or !$ses->isFriendlyFireActivated()){
							$fromEnt->sendTip($ses->translate(Phrases::PVP_ATTACK_FRIENDS, [
								"type" => SetFriendQuery::$TYPES[$type],
								"target" => $this->getInGameName()
							]));
							return false;
						}
					}
					$now = microtime(true);
					if($now - $this->lastHurtTime < $this->nextCooldownTimeout){
						return false;
					}
					$this->lastHurtTime = microtime(true);
					$this->nextCooldownTimeout = ClassicConsts::DEFAULT_COOLDOWN_TIMEOUT;
					if($event instanceof EntityDamageByChildEntityEvent and $event->getChild() instanceof Arrow){
						$fromEnt->getInventory()->addItem(Item::get(Item::ARROW, 0, 2));
						$fromEnt->getInventory()->sendContents([$fromEnt]);
					}
					// experimental
					$this->timesHitWithoutResponse = 0;
					++$ses->timesHitWithoutResponse;
					if($ses->timesHitWithoutResponse === 4){
						$ses->getPlayer()->respawnToAll();
					}
				}
			}
			$this->lastDamageBlock = $this->getPlayer()->getLevel()->getBlock($this->getPlayer());
			$this->lastDamageTime = microtime(true);
		}elseif($event->getCause() === EntityDamageEvent::CAUSE_FALL or $event->getCause() === EntityDamageEvent::CAUSE_LAVA){
			$last = $this->getPlayer()->getLastDamageCause();
			if($last instanceof EntityDamageByEntityEvent){
				$this->lastFallLavaCause = $last;
			}
		}
//		$this->getPlayer()->setNameTag($this->calculateNameTag(TextFormat::WHITE, $this->getPlayer()->getHealth() - $event->getFinalDamage()));
		$event->setDamage($damage);
		$deficit = $event->getFinalDamage() - $this->getPlayer()->getHealth();
		if($deficit > 0){
			$event->setDamage($event->getDamage() - $deficit);
		}
		return true;
	}
	public function onDeath(PlayerDeathEvent $event){
		if(!parent::onDeath($event)){
			return false;
		}
		$this->hasEquipped = false;
		$this->setCombatMode(false);
		$event->setDeathMessage("");
		$event->setDrops([]);
		$streak = $this->getCurrentStreak();
		$this->setCurrentStreak();
		$maxStreak = $this->getMaximumStreak();
		$this->setMaximumStreak(max($streak, $maxStreak));
		$cause = $this->getPlayer()->getLastDamageCause();
		$this->addDeath();
		if(!($cause instanceof EntityDamageEvent)){
			$this->send(Phrases::PVP_DEATH_GENERIC);
			return true;
		}
		if($cause instanceof EntityDamageByEntityEvent){
			$killer = $cause->getDamager();
			if($killer instanceof Player){
				/** @var static $ks */
				$ks = $this->getMain()->getSession($killer);
				if($ks instanceof Session){
					$kn = $ks->getInGameName();
					$ks->setCombatMode(false);
					$amount = ClassicConsts::getKillHeal($ks);
					$killer->heal($amount, new EntityRegainHealthEvent($killer, $amount, EntityRegainHealthEvent::CAUSE_CUSTOM));
				}
			}
			if(!isset($kn)){
				$kn = (new \ReflectionClass($killer))->getShortName();
				MUtils::word_camelToStd($kn);
				MUtils::word_addSingularArticle($kn);
			}
			$data = ["killer" => $kn, "action" => $this->translate(Phrases::PVP_ACTION_GENERIC)];
			if($cause instanceof EntityDamageByChildEntityEvent){
				$child = $cause->getChild();
				if($child instanceof Snowball){
					$data["action"] = $this->translate(Phrases::PVP_ACTION_SNOWBALL);
				}elseif($child instanceof Egg){
					$data["action"] = $this->translate(Phrases::PVP_ACTION_EGG);
				}elseif($child instanceof Projectile){
					$data["action"] = $this->translate(Phrases::PVP_ACTION_ARROW);
				}
			}
			$this->send(Phrases::PVP_DEATH_KILLED, $data);
			if(isset($ks) and ($ks instanceof ClassicSession)){
				$data["victim"] = $this->getInGameName();
				$ks->send(Phrases::PVP_KILL_KILLED, $data);
				$ks->addKill();
			}
		}elseif(($cause->getCause() === EntityDamageEvent::CAUSE_FALL or $cause->getCause() === EntityDamageEvent::CAUSE_LAVA) and microtime(true) - $this->lastDamageTime < 2.5){
			$knock = $this->lastFallLavaCause;
			if($knock === null){
				return true;
			}
			$block = $this->lastDamageBlock;
			$id = $block->getId();
			if($cause->getCause() === EntityDamageEvent::CAUSE_LAVA){
				$deathPhrase = Phrases::PVP_DEATH_LAVA;
				$killPhrase = Phrases::PVP_KILL_LAVA;
			}elseif($id === Block::LADDER){
				$deathPhrase = Phrases::PVP_DEATH_FALL_LADDER;
				$killPhrase = Phrases::PVP_KILL_FALL_LADDER;
			}elseif($id === Block::VINE){
				$deathPhrase = Phrases::PVP_DEATH_FALL_VINE;
				$killPhrase = Phrases::PVP_KILL_FALL_VINE;
			}else{
				$deathPhrase = Phrases::PVP_DEATH_FALL_GENERIC;
				$killPhrase = Phrases::PVP_KILL_FALL_GENERIC;
			}
			$killer = $knock->getDamager();
			if($killer instanceof Player and ($ks = $this->getMain()->getSession($killer)) instanceof Session){
				$kn = $ks->getInGameName();
			}else{
				$kn = (new \ReflectionClass($killer))->getShortName();
				MUtils::word_camelToStd($kn);
				MUtils::word_addSingularArticle($kn);
			}
			$data = ["killer" => $kn, "victim" => $this->getInGameName(), "action" => $this->translate(Phrases::PVP_ACTION_GENERIC)];
			if($knock instanceof EntityDamageByChildEntityEvent){
				$child = $knock->getChild();
				if($child instanceof Snowball){
					$data["action"] = $this->translate(Phrases::PVP_ACTION_SNOWBALL);
				}elseif($child instanceof Egg){
					$data["action"] = $this->translate(Phrases::PVP_ACTION_EGG);
				}elseif($child instanceof Projectile){
					$data["action"] = $this->translate(Phrases::PVP_ACTION_ARROW);
				}
			}
			$this->send($deathPhrase, $data);
			if(isset($ks) and $ks instanceof ClassicSession){
				$ks->send($killPhrase, $data);
				$ks->addKill();
			}
		}
		$this->setCombatMode(false);
		return true;
	}
	public function onHeal(EntityRegainHealthEvent $event){
		if(!parent::onHeal($event)){
			return false;
		}
		if($this->battle instanceof ClassicBattle){
			return false;
		}
//		$this->getPlayer()->setNameTag($this->calculateNameTag(TextFormat::WHITE, $event->getAmount() + $this->getPlayer()->getHealth()));
		return true;
	}
	public function onMove(PlayerMoveEvent $event){
		if(!parent::onMove($event) or $this->movementBlocked){
			return false;
		}
		/*if(ClassicConsts::spawnPortal($this->getPlayer())){
			$this->getPlayer()->teleport(ClassicConsts::getRandomSpawnPosition($this->getMain()->getServer()));
			$this->lastRespawnTime = microtime(true);
			$this->getPlayer()->addEffect(Effect::getEffect(Effect::INVISIBILITY)->setDuration(ClassicConsts::RESPAWN_INVINCIBILITY * 20)->setVisible(false));
		}*/
		if(!ClassicConsts::isSpawn($this->getPlayer()->getPosition())){
			if($this->currentKit instanceof ClassicKit){
				foreach($this->currentKit->getPowers() as $power){
					$power->onMove($this);
				}
			}
		}
		if($this->battle instanceof ClassicBattle){
			if($this->battle->getStatus() === ClassicBattle::STATUS_STARTING){
				if($event->getTo()->getX() != $event->getFrom()->getX() or $event->getTo()->getZ() != $event->getFrom()->getZ()){
					return false;
				}
			}
		}
		return true;
	}
	public function onConsume(PlayerItemConsumeEvent $event){
		if(!parent::onConsume($event)){
			return false;
		}
		$this->getPlayer()->heal(6, new EntityRegainHealthEvent($this->getPlayer(), 6, EntityRegainHealthEvent::CAUSE_EATING));
		return true;
	}
	public function login($method){
		parent::login($method);
		// $this->setCurrentKitById($this->getLoginDatum('pvp_kit'), $this->getKitLevelById($this->getLoginDatum('pvp_kit')));
		$this->setCurrentKitById(ClassicKit::KIT_ID_DEFAULT, 1);
		if($this->currentKit instanceof ClassicKit){
			$this->currentKit->equip($this);
		}

		$level = $this->getPlayer()->getLevel();
		$currentKitStand = new CurrentKitStand($this, $this->currentKit,
			new Position(-2.5, 7, 1.5, $level),
			0,
			[
				new Position(-4, 6, 1, $level),
				new Position(-3, 6, 2, $level),
				new Position(-2, 6, 1, $level),
				new Position(-3, 6, 0, $level)
			],
			[
				new Position(-4, 9, 1, $level),
				new Position(-3, 9, 2, $level),
				new Position(-2, 9, 1, $level),
				new Position(-3, 9, 0, $level)
			],
			new Position(0.5, 9.5, 1, $level)
		);
		$this->currentKitStand = $currentKitStand;
		$this->kitStands[$currentKitStand->getEid()] = $currentKitStand;


		$default = new KitStand($this, new DefaultKit(1),
			new Position(-2.5, 7, 14.5, $level),
			180,
			[
				new Position(-2, 6, 14, $level),
				new Position(-3, 6, 13, $level),
				new Position(-4, 6, 14, $level)
			],
			[
				new Position(-2, 9, 14, $level),
				new Position(-3, 9, 13, $level),
				new Position(-4, 9, 14, $level)
			],
			new Position(-5.5, 9.5, 14, $level)
		);
		$this->kitStands[$default->getEid()] = $default;

		/*$frozone = new KitStand($this, new FrozoneKit(1, $this->main->resetBlocksTask),
			new Position(-2.5, 7, -11.5, $level),
			0,
			[
				new Position(-1, 6, -11, $level),
				new Position(-2, 6, -10, $level),
				new Position(-3, 6, -11, $level)
			],
			[
				new Position(-1, 9, -11, $level),
				new Position(-2, 9, -10, $level),
				new Position(-3, 9, -11, $level)
			],
			new Position(0, 8, -11, $level)
		);
		$this->kitStands[$frozone->getEid()] = $frozone;*/


		$knight = new KitStand($this, new KnightKit(1),
			new Position(-15.5, 7, 1.5, $level),
			-90,
			[
				new Position(-16, 6, 2, $level),
				new Position(-15, 6, 1, $level),
				new Position(-16, 6, 0, $level)
			],
			[
				new Position(-16, 9, 2, $level),
				new Position(-15, 9, 1, $level),
				new Position(-16, 9, 0, $level)
			],
			new Position(-15, 9.5, -2, $level)
		);
		$this->kitStands[$knight->getEid()] = $knight;

		$pyro = new KitStand($this, new PyroKit(1),
			new Position(10.5, 7, 1.5, $level),
			90,
			[
				new Position(10, 6, 0, $level),
				new Position(9, 6, 1, $level),
				new Position(10, 6, 2, $level)
			],
			[
				new Position(10, 9, 0, $level),
				new Position(9, 9, 1, $level),
				new Position(10, 9, 2, $level)
			],
			new Position(10, 9.5, 4.5, $level)
		);
		$this->kitStands[$pyro->getEid()] = $pyro;



		//$this->onRespawn(new PlayerRespawnEvent($this->getPlayer(), $this->getPlayer()->getPosition()));
		//$this->getMain()->getServer()->getLevelByName("world_pvp")->addParticle(new FloatingTextParticle(new Vector3(304, 49, -150), $this->translate(Phrases::PVP_LEAVE_SPAWN_HINT)), [$this->getPlayer()]);
		foreach($this->getMain()->getQueueBlocks() as $queueBlock){
			$queueBlock->addSession($this);
		}
		if(IS_HALLOWEEN_MODE){
			foreach(ClassicConsts::getGhastLocations($this->getMain()->getServer()) as $loc){
				$particle = new SpawnGhastParticle($loc->x, $loc->y, $loc->z);
				$particle->yaw = $loc->yaw;
				$particle->pitch = $loc->pitch;
				$loc->getLevel()->addParticle($particle, [$this->getPlayer()]);
			}
		}
	}
	public function onClientDisconnect(){
		if($this->isCombatMode()){
			$this->setCoins($this->getCoins() - ($take = $this->getCombatLogPenalty()));
			$this->getMain()->sendPrivateMessage($this->getUid(), "You logged out while in combat mode, so $take coins have been taken from you as penalty.");
		}
	}
	public function onQuit(){
		if($this->getBattle() instanceof ClassicBattle){
			$this->getBattle()->setStatus(ClassicBattle::STATUS_ENDING, $this->getPlayer()->getName() . " left the Battle.", $this->getBattle()->getOverallWinner());
		}
		foreach($this->getMain()->getQueueBlocks() as $queueBlock){
			$queueBlock->removeSession($this);
		}
	}
	public function onRespawn(PlayerRespawnEvent $event){
		parent::onRespawn($event);
//		$health = $this->getPlayer()->getAttribute()->getAttribute(AttributeManager::MAX_HEALTH);
		/*$health = $this->getPlayer()->getAttribute()->addAttribute(AttributeManager::MAX_HEALTH, "generic.health", 0.0, 40.0, 40.0, true);
		$health->send();
		$hunger = $this->getPlayer()->getAttribute()->getAttribute(AttributeManager::MAX_HUNGER);
		$hunger->setValue(19.0);
		$hunger->send();
		$hunger = $this->getPlayer()->getAttribute()->addAttribute(AttributeManager::MAX_HUNGER, "player.health", 0.0, 20.0, 19.0, true);
		$hunger->setValue(19.0);
		$hunger->send();*/
		$this->getPlayer()->setMaxHealth(40);
		$this->getPlayer()->setHealth(40); // float(20)
		$event->setRespawnPosition($spawn = ClassicConsts::getSpawnPosition($this->getMain()->getServer()));
		$this->getPlayer()->teleport($spawn);
		if($this->currentKit instanceof ClassicKit){
			$this->currentKit->equip($this);
		}
//		$this->getPlayer()->setNameTag($this->calculateNameTag(TextFormat::WHITE, $this->getPlayer()->getMaxHealth()));
		//$this->getPlayer()->getInventory()->clearAll();
		//$this->getPlayer()->getInventory()->sendArmorContents($this->getPlayer()->getInventory()->getViewers());
	}
	public function onPlace(BlockPlaceEvent $event){
		return false;
	}
	public function onInteract(PlayerInteractEvent $event){
		if(!parent::onInteract($event)){
			return false;
		}
		$block = $event->getBlock();
		$this->getMain()->getLogger()->info("Touched {$block->x} {$block->y} {$block->z}");
		foreach($this->getMain()->getQueueBlocks() as $queueBlock){
			if($block->getX() === $queueBlock->getBlock()->getX() and $block->getY() === $queueBlock->getBlock()->getY() and $block->getZ() === $queueBlock->getBlock()->getZ()){
				if(!$this->isQueueing){
					$queue = new ClassicBattleQueue($this->getMain()->getQueueManager(), $this, false, false, $queueBlock->getType());
					$this->getPlayer()->sendMessage(TextFormat::GOLD . "You are queueing for a " . TextFormat::RED . "Battle" . TextFormat::GOLD . " with type " . TextFormat::RED . "{$queueBlock->getType()}v{$queueBlock->getType()}");
					$this->isQueueing = true;
					$this->getMain()->getQueueManager()->addQueue($queue);
				}else{
					$this->getPlayer()->sendMessage(TextFormat::GOLD . "You are already queueing.");
				}
			}
		}
		$pos = new Position($block->getX(), $block->getY(), $block->getZ(), $event->getPlayer()->getLevel());
		foreach($this->kitStands as $kitStand){
			if($kitStand->isNextPosition($pos)){
				$kitStand->next();
			}elseif($kitStand->isBackPosition($pos)){
				$kitStand->back();
			}
		}
	}
	public function onBreak(BlockBreakEvent $event){
		if(!parent::onBreak($event)){
			return false;
		}
//		return $this->isBuilder();
		return false;
	}
	public function onOpenInv(InventoryOpenEvent $event){
		if(!parent::onOpenInv($event)){
			return false;
		}
		return !($event->getInventory() instanceof ChestInventory);
	}
	public function onPickupArrow(InventoryPickupArrowEvent $event){
		return parent::onPickupArrow($event);
	}
	public function onItemHold(PlayerItemHeldEvent $event){
		if(!parent::onHoldItem($event)){
			return false;
		}
		if($this->currentKit instanceof ClassicKit){
			foreach($this->currentKit->getPowers() as $power){
				if(isset($power->item)){
					if($event->getItem()->equals($power->item)){
						if($power->isActive()){
							$this->getPlayer()->sendTip(TextFormat::RED . "This power is already active.");
						}else{
							foreach($this->currentKit->getPowers() as $powerTwo){
								if($powerTwo->isActive() and !$powerTwo->isPermanent){
									$this->getPlayer()->sendTip(TextFormat::RED . "You currently have another power active.");
									return false;
									break;
								}
							}
							$time = $power->getTimeTillNextActivate();
							if($time <= 0){
								$power->setActive(true);
							}else{
								$this->getPlayer()->sendTip(TextFormat::RED . $time . " seconds left before you can use this power.");
							}
						}
						return false;
						break;
					}
				}
			}
		}
	}

	/**
	 * @return ClassicBattle|null
	 */
	public function getBattle(){
		return $this->battle;
	}
	/**
	 * @param ClassicBattle|null
	 */
	public function setBattle($value){
		$this->battle = $value;
	}
	public function decrementHandItem(){
		$inv = $this->getPlayer()->getInventory();
		$item = $inv->getItemInHand();
		$item->setCount($item->getCount() - 1);
		$inv->setItemInHand($item);
	}
	public function getCurrentStreak(){
		return $this->getLoginDatum("pvp_curstreak");
	}
	public function getMaximumStreak(){
		return $this->getLoginDatum("pvp_maxstreak");
	}
	public function setMaximumStreak($kills){
		$this->setLoginDatum("pvp_maxstreak", $kills);
	}
	public function setCurrentStreak($kills = 0){
		$this->setLoginDatum("pvp_curstreak", $kills);
	}
	public function addKill(){
		$kills = $this->incrementLoginDatum("pvp_kills");
		if(microtime(true) - $this->lastKillTime < $this->nextKillstreakTimeout){
			$streak = $this->incrementLoginDatum("pvp_curstreak");
		}else{
			$this->setLoginDatum("pvp_curstreak", $streak = 1);
		}
		$ceilStreak = ceil($streak / 5) * 5;
		$coins = round(ClassicConsts::COINS_ON_KILL * 3.322 * log($ceilStreak + 1, 10) * $ceilStreak ** -0.2);
		$this->grantTeamPoints(floor($coins));
		list($add, $final) = $this->grantCoins($coins);
		$this->lastKillTime = microtime(true);
		$this->send(Phrases::PVP_KILL_INFO, [
			"literal" => $kills,
			"ord" => $kills . MUtils::num_getOrdinal($kills),
			"streak" => $streak,
			"streakord" => $streak . MUtils::num_getOrdinal($streak),
			"coins" => $final,
			"added" => $add
		]);
		$rand = mt_rand(0, 999);
		if($rand < 1){
			$bonus = mt_rand(5000, 10000);
		}elseif($rand < 6){
			$bonus = mt_rand(1000, 5000);
		}elseif($rand < 16){
			$bonus = mt_rand(500, 1000);
		}
		if(isset($bonus)){
			$this->grantCoins($bonus, true);
		}
	}
	public function addDeath(){
		$deaths = $this->incrementLoginDatum("pvp_deaths");
		$this->send(Phrases::PVP_DEATH_INFO, [
			"literal" => $deaths,
			"ord" => $deaths . MUtils::num_getOrdinal($deaths)
		]);
		$this->takeTeamPoints(5);
	}
	/**
	 * @return int
	 */
	public function getGlobalRank(){
		return $this->globalRank;
	}
	/**
	 * @param int $globalRank
	 */
	public function setGlobalRank($globalRank){
		$this->globalRank = $globalRank;
	}
	/**
	 * @return ClassicPlugin
	 */
	public function getMain(){
		return $this->main;
	}
	public function getJoinedClassicSince(){
		return $this->getLoginDatum("pvp_init");
	}
	/**
	 * @return boolean
	 */
	public function isInvincible(){
		return $this->invincible;
	}
	/**
	 * @param boolean $invincible
	 */
	public function setInvincible($invincible){
		$this->invincible = $invincible;
	}
	/**
	 * @return boolean
	 */
	public function isMovementBlocked(){
		return $this->movementBlocked;
	}
	/**
	 * @param boolean $movementBlocked
	 */
	public function setMovementBlocked($movementBlocked){
		$this->movementBlocked = $movementBlocked;
	}
	/**
	 * @param int $left
	 * @return bool
	 */
	public function isCombatMode(&$left = 0){
		$left = $this->combatModeExpiry - microtime(true);
		return $left > 0;
	}
	/**
	 * @param bool $on
	 */
	public function setCombatMode($on = true){
		$this->combatModeExpiry = ($on ? (microtime(true) + ClassicConsts::COMBAT_MODE_COOLDOWN) : 0);
	}
	public function getKills(){
		return $this->getLoginDatum("pvp_kills");
	}
	public function getDeaths(){
		return $this->getLoginDatum("pvp_deaths");
	}
	/**
	 * @return boolean
	 */
	public function isFriendlyFireActivated(){
		return $this->friendlyFireActivated;
	}
	/**
	 * @param boolean $friendlyFireActivated
	 */
	public function setFriendlyFireActivated($friendlyFireActivated){
		$this->friendlyFireActivated = $friendlyFireActivated;
	}
	public function getCombatLogPenalty(){
		return max(10, ceil($this->getCoins() * 0.0375));
	}

	public function equip(){
		$this->currentKit->equip($this);
		/*$inv = $this->getPlayer()->getInventory();
		if($inv === null){
			return;
		}
		$inv->clearAll();
		$inv->setHelmet(new GoldHelmet);
		$inv->setChestplate(new ChainChestplate);
		$inv->setLeggings(new LeatherPants);
		$inv->setBoots(new GoldBoots);
		$inv->sendArmorContents([$this->getPlayer()]);
		$inv->setItem(0, new Bow);
		$inv->setItem(1, new DiamondSword);
//		$inv->setItem(2, Item::get(Item::BAKED_POTATO, 0, 32));
		$inv->setItem(3, Item::get(Item::ARROW, 0, 16));
		$inv->setHotbarSlotIndex(0, 0);
		$inv->setHotbarSlotIndex(1, 1);
		$inv->setHotbarSlotIndex(2, 2);
		$inv->setHotbarSlotIndex(3, 3);
		$inv->sendContents([$this->getPlayer()]);*/
	}
	public function halfSecondTick(){
		parent::halfSecondTick();
		if((++$this->counter) === 10){
			$this->counter = 0;
			if($this->getPlayer()->getHealth() > 0 and $this->getPlayer()->getHealth() !== $this->getPlayer()->getMaxHealth()){
				$amount = ClassicConsts::getAutoHeal($this);
				$this->getPlayer()->heal($amount, new EntityRegainHealthEvent($this->getPlayer(), $amount, EntityRegainHealthEvent::CAUSE_REGEN));
			}
			//$this->getPlayer()->setFood(19);
			if($this->currentKit instanceof ClassicKit){
				foreach($this->currentKit->getPowers() as $power){
					if(!$power->isPermanent){
						if($power->isActive()){ // updates
							$this->getPlayer()->sendTip(TextFormat::RED . $power->getName() . TextFormat::GREEN . " power time left: " . ($power->getDuration() - $power->getTimeActive()));
						}
					}
				}
			}
		}
		if($this->isPlaying()){
			$respawn = (int) (ClassicConsts::RESPAWN_INVINCIBILITY - microtime(true) + $this->lastRespawnTime);
			if($respawn > 0){
				$this->setInvincible(true);
				$this->setMaintainedPopup($this->translate(Phrases::PVP_INVINCIBILITY_LEFT, ["left" => $respawn]));
			}elseif($respawn === 0){
				$this->setMaintainedPopup();
				$this->getPlayer()->sendPopup($this->translate(Phrases::PVP_INVINCIBILITY_OFF));
				$this->setInvincible(false);
				$this->hasEquipped = true;
				$this->equip();
				//$this->getPlayer()->setFood(19);
				//$this->getPlayer()->setFoodEnabled(false);
				// night vision not found
				// $this->getPlayer()->addEffect(Effect::getEffect(Effect::NIGHT_VISION)->setVisible(false)->setAmplifier(0x7FFFFF));
			}
		}
		$nameTag = $this->calculateNameTag();
		if($nameTag !== $this->getPlayer()->getNameTag()){
			if(!($this->getBattle() instanceof ClassicBattle)){
				$this->getPlayer()->setNameTag($nameTag);
			}
		}
	}
	protected function chatPrefix(){
		if($this->isStatsPublic() and $this->globalRank > 0 and $this->getKills() > 0){
			return Phrases::VAR_symbol . "{" . Phrases::VAR_em . $this->getKills() . Phrases::VAR_em2 . "#" . $this->globalRank . Phrases::VAR_symbol . "}";
		}
		return "";
	}
	public function calculateNameTag($nameColor = TextFormat::WHITE, $health = null){
		$out = parent::calculateNameTag();
		if(ENABLE_HEALTHBAR){
			if($health === null){
				$health = $this->getPlayer()->getHealth();
			}
			$out .= "\n" . TextFormat::DARK_GREEN . ($health / 2) . " / " . ($this->getPlayer()->getMaxHealth() / 2);
		}
		return $out;
	}
	protected function sendMaintainedPopup(){
		$popup = $this->getPopup();
		$popup = $popup === null ? "" : $popup;
		$popupLines = MUtils::align($popup, " ", MUtils::ALIGN_CENTER, true);
		$out = "";
		if($this->isCombatMode($time)){
			$pvpLogLines = MUtils::align($this->translate(Phrases::PVP_CMD_PVP_LOG_POPUP, [
				"penalty" => $this->getCombatLogPenalty(),
				"time" => (int) $time
			]), " ", MUtils::ALIGN_CENTER, true);
			$size = max(count($popupLines), count($pvpLogLines));
			for($i = 0; $i < $size; $i++){
				if(!isset($popupLines[$i])){
					$popupLines[$i] = MUtils::align($pvpLogLines[$i] . "\n", " ", MUtils::ALIGN_LEFT, true)[1];
				}elseif(!isset($pvpLogLines[$i])){
					$pvpLogLines[$i] = MUtils::align($popupLines[$i] . "\n", " ", MUtils::ALIGN_LEFT, true)[1];
				}
			}
			for($i = 0; $i < $size; $i++){
				$out .= $popupLines[$i] . $pvpLogLines[$i] . "\n";
			}
		}else{
			$out = implode("\n", $popupLines);
		}
		$this->getPlayer()->sendPopup($out);
	}
//	public function onInteract(PlayerInteractEvent $event){
//		if(!parent::onInteract($event)){
//			return false;
//		}
//		$this->checkInteractWithFood($event);
//		return true;
//	}
//	protected function checkInteractWithFood(PlayerInteractEvent $event){
//		$items = [ //TODO: move this to item classes
//			Item::APPLE => 4,
//			Item::MUSHROOM_STEW => 10,
//			Item::BEETROOT_SOUP => 10,
//			Item::BREAD => 5,
//			Item::RAW_PORKCHOP => 3,
//			Item::COOKED_PORKCHOP => 8,
//			Item::RAW_BEEF => 3,
//			Item::STEAK => 8,
//			Item::COOKED_CHICKEN => 6,
//			Item::RAW_CHICKEN => 2,
//			Item::MELON_SLICE => 2,
//			Item::GOLDEN_APPLE => 10,
//			Item::PUMPKIN_PIE => 8,
//			Item::CARROT => 4,
//			Item::POTATO => 1,
//			Item::BAKED_POTATO => 6,
//			Item::COOKIE => 2,
//			Item::COOKED_FISH => [
//				0 => 5,
//				1 => 6
//			],
//			Item::RAW_FISH => [
//				0 => 2,
//				1 => 2,
//				2 => 1,
//				3 => 1
//			],
//		];
//		if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK and isset($items[$id = $event->getItem()->getId()])){
//			$health = $items[$id];
//			if(is_array($health)){
//				$health = $health[$event->getItem()->getDamage()];
//			}
//			$this->eatFoodAction($health);
//			$this->decrementHandItem();
//		}
//	}
//	public function eatFoodAction($health){
//		$this->getPlayer()->heal($health, new EntityRegainHealthEvent($this->getPlayer(), $health, EntityRegainHealthEvent::CAUSE_EATING));
//		$effect = $this->getPlayer()->getEffect(Effect::SLOWNESS);
//		if(microtime(true) - $this->lastEat < 2){
//			return;
//		}
//		$this->lastEat = microtime(true);
//		if($effect === null){
//			$effect = Effect::getEffect(Effect::SLOWNESS)->setDuration(40)->setVisible(false)->setAmplifier(1);
//		}else{
//			$this->getPlayer()->removeEffect(Effect::SLOWNESS);
//			$effect->setDuration(40);
//		}
//		$this->getPlayer()->addEffect($effect);
//	}
}

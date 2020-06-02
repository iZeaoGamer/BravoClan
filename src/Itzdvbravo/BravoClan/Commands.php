<?php

namespace Itzdvbravo\BravoClan;

use Itzdvbravo\BravoClan\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class Commands{
    private $plugin;
    public $invite = [];

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    /**
     * @param CommandSender $player
     * @param Command $cmd
     * @param string $label
     * @param array $args
     * @return bool
     */
    public function command(CommandSender $player, Command $cmd, string $label, array $args) {
        if (count($args) === 0){
            $this->help($player);
        } else {
            if ($cmd->getName() === "clan"){
                switch ($args[0]){
                    default:
                        $this->help($player);
                        break;
                    case "create":
                        if ($this->plugin->getDatabase()->isInClan(strtolower($player->getName()))){
                            $player->sendMessage("§4You are already in a clan, you can't create one");
                        } else {
                            if (empty($args[1])){
                                $player->sendMessage("§eProvide Clan Name");
                            } elseif (!ctype_alpha("{$args[1]}")){
                                $player->sendMessage("§4Clan name is invalid, it should contain letters only");
                            }else{
                                if ($this->plugin->getDatabase()->clanExist($args[1])){
                                    $player->sendMessage("§4Clan name already exists");
                                } elseif (strlen($args[1]) > 13){
                                    $player->sendMessage("§eclan name can't be longer than 13 characters");
                                } else {
                                    $this->plugin->getDatabase()->setClan($args[1], $player->getName());
                                    $this->plugin->getClans()->player[strtolower($player->getName())] = $args[1];
                                    $player->sendMessage("§aClan have been created, do /clan info");
                                }
                            }
                        }
                        break;
                    case "invite":
                        if (!$this->plugin->getDatabase()->isInClan(strtolower($player->getName()))){
                            $player->sendMessage("§4You aren't in a clan");
                        } elseif ($this->plugin->getDatabase()->getClan($this->plugin->getClans()->player[strtolower($player->getName())])["leader"] !== strtolower($player->getName())){
                            $player->sendMessage("§4You aren't the leader of the clan");
                        } else {
                            if (empty($args[1])){
                                $player->sendMessage("§4Provide a player to invite");
                            } elseif (!$this->plugin->isOnline($args[1])){
                                $player->sendMessage("§ePlayer not found");
                            } else {
                                $person = $this->plugin->getPlayerToString($args[1]);
                                if ($this->plugin->getDatabase()->isInClan(strtolower($person->getName()))){
                                    $player->sendMessage("§eThat player is already in a clan");
                                } elseif ($this->plugin->getDatabase()->getClan($this->plugin->getClans()->player[strtolower($player->getName())])["tm"] >
                                    $this->plugin->getDatabase()->getClan($this->plugin->getClans()->player[strtolower($player->getName())])["maxtm"]){
                                    $player->sendMessage("§4You can't add more ppl in the clan, levelup to get more space");
                                } else {
                                    if (array_key_exists("{$person->getName()}", $this->invite)){
                                        if ($this->invite[strtolower($person->getName())][0] + 30 <= time()){
                                            unset($this->invite[strtolower($person->getName())]);
                                        } else {
                                            $time = $this->invite[strtolower($person->getName())][0] + 30 - time();
                                            $player->sendMessage("§eYou can invite {$person->getName()} after {$time} seconds");
                                            return true;
                                        }
                                    }
                                    $clan = $this->plugin->getDatabase()->getClan($this->plugin->getClans()->player[strtolower($player->getName())])["clan"];
                                    $person->sendMessage("§eYou have been invited to {$clan} by {$player->getName()}, Do /clan accept,will last for 30 seconds");
                                    $this->invite[strtolower($person->getName())] = [time(), $clan];
                                    $player->sendMessage("§e{$person->getName()} has been invited, you can invite him again after 30 seconds");
                                }
                            }
                        }
                        break;
                    case "accept":
                        if ($this->plugin->getDatabase()->isInClan(strtolower($player->getName()))) {
                            $player->sendMessage("§eYou are already in a clan");
                        } elseif (!array_key_exists(strtolower($player->getName()), $this->invite)){
                            $player->sendMessage("§eYou have no invites");
                        } else {
                            if ($this->invite[strtolower($player->getName())][0] + 30 <= time()){
                                $player->sendMessage("§eInvite have been expired");
                                unset($this->invite[strtolower($player->getName())]);
                            } else {
                                $clan = $this->plugin->getDatabase()->getClan($this->invite[strtolower($player->getName())][1]);
                                if ($clan["tm"] > $clan["maxtm"]){
                                    $player->sendMessage("§eSomeone else has joined the clan and the clan has no space left");
                                } else {
                                    $this->plugin->getDatabase()->setMember($clan["clan"], $player->getName());
                                    $this->plugin->getDatabase()->setTm($clan["clan"], $clan["tm"] + 1);
                                    $this->plugin->getClans()->player[strtolower($player->getName())] = $clan["clan"];
                                    $player->sendMessage("§eYou have joined {$clan['clan']}\n§aLeader §f- §e{$clan['leader']} §aTotal Members §f- §e{$clan['tm']}");
                                }
                            }
                        }
                        break;
                    case "kick":
                        if (!$this->plugin->getDatabase()->isInClan(strtolower($player->getName()))) {
                            $player->sendMessage("§eYou aren't in a clan");
                        }  elseif ($this->plugin->getDatabase()->getClan(Main::$clan->player[strtolower($player->getName())])["leader"] !== strtolower($player->getName())){
                            $player->sendMessage("§eYou aren't the leader of the clan");
                        } else {
                            if (empty($args[1])){
                                $player->sendMessage("§eProvide a player to kick");
                            } elseif(strtolower($args[1]) === strtolower($player->getName())) {
                                $player->sendMessage("§eYou can't kick yourself");
                            }else {
                                if ($this->plugin->getDatabase()->isInClan(strtolower($args[1]))){
                                    if ($this->plugin->getDatabase()->getMember(strtolower($args[1]))['clan'] !== $this->plugin->getDatabase()->getClan
                                        ($this->plugin->getClans()->player[strtolower($player->getName())])){
                                        $player->sendMessage("§eThat player isn't in your clan");
                                    } else {
                                        $clan = $this->plugin->getDatabase()->getClan($this->plugin->getDatabase()->getMember(strtolower($args[1]))["clan"]);
                                        $member = $this->plugin->getDatabase()->getMember(strtolower($args[1]));
                                        $this->plugin->getDatabase()->setKills($clan["clan"], $clan["kills"] - $member["kills"]);
                                        $this->plugin->getDatabase()->setDeaths($clan["clan"], $clan["deaths"] - $member["deaths"]);
                                        $this->plugin->getDatabase()->removeMember(strtolower($args[1]));
                                        $this->plugin->getDatabase()->setTm($clan['clan'], $clan['tm'] - 1);
                                        if ($this->plugin->isOnline($args[1])){
                                            unset($this->plugin->getClans()->player[strtolower($args[1])]);
                                        }
                                        $player->sendMessage("§eThat player have been removed");
                                    }
                                } else {
                                    $player->sendMessage("§eThat player isn't in any gang");
                                }
                            }
                        }
                        break;
                    case "leave":
                        $m = strtolower($player->getName());
                        if (!$this->plugin->getDatabase()->isInClan(strtolower($player->getName()))) {
                            $player->sendMessage("§eYou aren't in a clan");
                        }  elseif ($this->plugin->getDatabase()->getClan($this->plugin->getDatabase()->getMember(strtolower($player->getName()))["clan"])["leader"] === $m){
                            $player->sendMessage("§eYou cannot leave your clan, as you are the leader, do /clan delete to delete");
                        } else {
                            $clan = $this->plugin->getDatabase()->getClan($this->plugin->getDatabase()->getMember(strtolower($player->getName()))["clan"]);
                            $member = $this->plugin->getDatabase()->getMember($player->getName());
                            $this->plugin->getDatabase()->removeMember($player->getName());
                            $this->plugin->getDatabase()->setKills($clan["clan"], $clan["kills"] - $member["kills"]);
                            $this->plugin->getDatabase()->setDeaths($clan["clan"], $clan["deaths"] - $member["deaths"]);
                            $this->plugin->getDatabase()->setTm($clan['clan'], $clan['tm'] - 1);
                            unset($this->plugin->getClans()->player[strtolower($player->getName())]);
                            $player->sendMessage("§eLeft the clan");
                        }
                        break;
                    case "members":
                        if (empty($args[1])) {
                            if (!$this->plugin->getDatabase()->isInClan(strtolower($player->getName()))) {
                                $player->sendMessage("§eYou aren't in a clan, you can provide a clan to get info of");
                                return true;
                            } else {
                                $clan = $this->plugin->getDatabase()->getClan($this->plugin->getClans()->player[strtolower($player->getName())]);
                            }
                        } else {
                            if (!$this->plugin->getDatabase()->clanExist($args[1])){
                                $player->sendMessage("§eClan not found");
                                return true;
                            } else {
                                $clan = $this->plugin->getDatabase()->getClan($args[1]);
                            }
                        }
                        $m = $this->plugin->getDatabase()->clanMembers($clan['clan']);
                        foreach ($m as $person){
                            if ($this->plugin->isOnline($person)){
                                $info = $this->plugin->getDatabase()->getMember($person);
                                $player->sendMessage("§e{$person} [{$info['kills']}/{$info['deaths']}] [§aON§e]");
                            } else {
                                $info = $this->plugin->getDatabase()->getMember($person);
                                $player->sendMessage("§e{$person} [{$info['kills']}/{$info['deaths']}] [§4OFF§e]");
                            }
                        }
                        break;
                    case "delete":
                        if (!$this->plugin->getDatabase()->isInClan(strtolower($player->getName()))) {
                            $player->sendMessage("§eYou aren't in a clan");
                        } elseif ($this->plugin->getDatabase()->getClan($this->plugin->getClans()->player[strtolower($player->getName())])["leader"] !== strtolower($player->getName())){
                            $player->sendMessage("§eYou aren't the leader of the clan");
                        } else {
                            $clan = $this->plugin->getDatabase()->getClan($this->plugin->getClans()->player[strtolower($player->getName())]);
                            $this->plugin->getDatabase()->removeClan($clan['clan']);
                            $player->sendMessage("§4Your clan have been deleted");
                        }
                        break;
                    case "info":
                        if (empty($args[1])){
                            if (!$this->plugin->getDatabase()->isInClan(strtolower($player->getName()))) {
                                $player->sendMessage("§eYou aren't in a clan, you can provide a clan to get info of");
                                return true;
                            } else {
                                $clan = $this->plugin->getDatabase()->getClan($this->plugin->getClans()->player[strtolower($player->getName())]);
                            }
                        } else {
                            if (!$this->plugin->getDatabase()->clanExist($args[1])){
                                $player->sendMessage("§eClan not found");
                                return true;
                            } else {
                                $clan = $this->plugin->getDatabase()->getClan($args[1]);
                            }
                        }
                        $m = $this->plugin->getDatabase()->getMembersByClan($clan['clan']);
                        $player->sendMessage("§e<<<<<<<<<<<<<<<{$clan['clan']}>>>>>>>>>>>>>>");
                        $player->sendMessage("§eLeader: {$clan['leader']}");
                        $player->sendMessage("§eMembers: {$clan['tm']}/{$clan['maxtm']}");
                        if ($m !== Null) {
                            $members = implode(', ', $m);
                            $player->sendMessage("§b{$members}");
                        }
                        $player->sendMessage("§eLevel: {$clan['lvl']}");
                        $player->sendMessage("§eXP: {$clan['xp']}/{$clan['nex']}");
                        $player->sendMessage("§eKDR: {$clan['kills']}/{$clan['deaths']}");
                        $player->sendMessage("§e--------------------------------------------");
                        break;
                    case "chat":
                        if (!$this->plugin->getDatabase()->isInClan(strtolower($player->getName()))){
                            $player->sendMessage("§eYou aren't in a clan");
                        } else {
                            if (array_key_exists(strtolower($player->getName()), $this->plugin->getClans()->chat)){
                                unset($this->plugin->getClans()->chat[strtolower($player->getName())]);
                                $player->sendMessage("§eYou have left the clan chat");
                            } else {
                                $clan = $this->plugin->getDatabase()->getMember(strtolower($player->getName()))['clan'];
                                $this->plugin->getClans()->chat[strtolower($player->getName())] = $clan;
                                $player->sendMessage("§aYou have joined the clan chat");
                            }
                        }
                        break;
                    case "top":
                        $top = Main::$db->query("SELECT clan FROM clans ORDER BY level DESC LIMIT 10");
                        $counter = 0;
                        $player->sendMessage("§e+=TOP 10 Gangs=+");
                        while ($resultAr = $top->fetchArray(SQLITE3_ASSOC)) {
                            $counter2 = $counter + 1;
                            $clan = $this->plugin->getDatabase()->getClan($resultAr['clan']);
                            $player->sendMessage("§e{$counter2} -> {$clan['clan']} with {$clan['lvl']} Level §6{$clan['xp']}/{$clan['nex']}");
                            $counter= $counter + 1;
                        }
                        break;
                }
            }
        }
        return true;
    }

    /**
     * @param Player $player
     */
    public function help(Player $player){
        $player->sendMessage("§e/clan create - create clan");
        $player->sendMessage("§e/clan invite - invite players to clan");
        $player->sendMessage("§e/clan accept - accept clan invitation");
        $player->sendMessage("§e/clan kick - kick players from clan");
        $player->sendMessage("§e/clan leave - leave your clan");
        $player->sendMessage("§e/clan delete - delete your clan");
        $player->sendMessage("§e/clan info - get clan info of your or other clans");
        $player->sendMessage("§e/clan members - get members info from your clan/other's clan");
        $player->sendMessage("§e/clan chat - join clan chat");
        $player->sendMessage("§e/clan top - get top 10 clans");
    }
}

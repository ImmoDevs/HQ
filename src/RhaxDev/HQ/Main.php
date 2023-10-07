<?php

namespace RhaxDev\HQ;

use pocketmine\Server;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\command\{Command, CommandSender};
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\event\block\{BlockBreakEvent, BlockPlaceEvent};
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener {
    
    public function onEnable() : void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveResource("allquest.yml");
        $this->saveResource("questdata.yml");
        $this->saveResource("config.yml");
        $this->lg = new Config($this->getDataFolder(). "allquest.yml", Config::YAML);
        $this->dt = new Config($this->getDataFolder(). "questdata.yml", Config::YAML);
        $this->cfg = new Config($this->getDataFolder(). "config.yml", Config::YAML);
        $this->api = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        if($this->api == null){
            $this->getLogger()->error("EconomyAPI Plugin Must Added!");
            $this->getServer()->shutdown();
        }
    }
    
    public function onCommand(CommandSender $p, Command $cmd, String $label, array $args) : bool {
        if($cmd->getName() === 'quest'){
            if($p instanceof Player){
                $arg = array_shift($args);
                switch($arg){
                    case "addquest":
                        if($p->hasPermission("quest.addquest")){
                            $this->addQuestUI($p);
                        }else{
                            $p->sendMessage("§cYou Don't Have Permission To Use This Commands!");
                        }
                    break;
                    case "deletequest":
                        if($p->hasPermission("quest.deletequest")){
                            $this->deleteQuestUI($p);
                        }else{
                            $p->sendMessage("§cYou Don't Have Permission To Use This Commands!");
                        }
                    break;
                    case "help":
                        $p->sendMessage("§l§a>>> §bQuestCommands §l§a<<<\n§e/quest §fTo Open Quest Main Menu\n§e/quest addquest §fTo Add Quest\n§e/quest deletequest §fTo Delete Quest");
                    break;
                    default:
                        $this->sendQuestMenu($p);
                    break;
                }
            }else{
                $p->sendMessage("InGame Only Saudaraku <3");
            }
        }
        return true;
    }
    
    public function onJoin(PlayerJoinEvent $e){
        $p = $e->getPlayer();
        if(isset($this->dt->getAll()[$p->getName()])){
            if($this->dt->getAll()[$p->getName()][0] !== "true" && $this->dt->getAll()[$p->getName()][0] !== "false"){
                $this->dt->set($p->getName(), ["false", "null", "null", "null"]);
                $this->dt->save();
            }
        }else{
            $this->dt->set($p->getName(), ["false", "null", "null", "null"]);
            $this->dt->save();
        }
    }
    
    public function onBreak(BlockBreakEvent $e){
        $p = $e->getPlayer();
        $b = $e->getBlock();
        $dt = $this->dt->getAll()[$p->getName()];
        if($dt[0] == "true"){
            if($dt[1] == "break"){
                $x = explode(":", $dt[2]);
                if($b->getTypeId() == $x[0] && $b->getMeta() == $x[1]){
                    $c = $dt[3] + 1;
                    $this->dt->set($p->getName(), ["true", "break", $dt[2], $c]);
                    $this->dt->save();
                    $p->sendTip("§l§aQuestInfo:\n§b{$c}§f/§b{$x[2]}");
                }
                if($dt[3] == $x[2]){
                    $price = mt_rand((int)$this->cfg->get("min-reward"), (int)$this->cfg->get("max-reward"));
                    $this->resetQuest($p);
                    $this->api->addMoney($p, $price);
                    $p->sendMessage("§l§aYou Succesfully Finish The Quest! Present Of This You Get {$price} Money!");
                }
            }
        }
    }
    
    public function onPlace(BlockPlaceEvent $e){
        $p = $e->getPlayer();
        $b = $e->getBlock();
        $dt = $this->dt->getAll()[$p->getName()];
        if($dt[0] == "true"){
            if($dt[1] == "place"){
                $x = explode(":", $dt[2]);
                if($b->getTypeId() == $x[0] && $b->getMeta() == $x[1]){
                    $c = $dt[3] + 1;
                    $this->dt->set($p->getName(), ["true", "place", $dt[2], $c]);
                    $this->dt->save();
                    $p->sendTip("§l§aQuestInfo:\n§b{$c}§f/§b{$x[2]}");
                }
                if($dt[3] == $x[2]){
                    $price = mt_rand($this->cfg->get("min-reward"), $this->cfg->get("max-reward"));
                    $this->resetQuest($p);
                    $this->api->addMoney($p, $price);
                    $p->sendMessage("§l§aYou Succesfully Finish The Quest! Present Of This You Get {$price} Money!");
                }
            }
        }
    }
    
    public function onCraft(CraftItemEvent $e){
        $p = $e->getPlayer();
        $bs = $e->getOutputs();
        $dt = $this->dt->getAll()[$p->getName()];
        if($dt[0] == "true"){
            if($dt[1] == "craft"){
                $x = explode(":", $dt[2]);
                foreach($bs as $b){
                    if($b->getTypeId() == $x[0] && $b->getMeta() == $x[1]){
                        $c = $dt[3] + 1;
                    $this->dt->set($p->getName(), ["true", "craft", $dt[2], $c]);
                    $this->dt->save();
                    $p->sendMessage("§l§aQuestInfo:\n§b{$c}§f/§b{$x[2]}");
                    }
                    if($dt[3] == $x[2]){
                        $price = mt_rand($this->cfg->get("min-reward"), $this->cfg->get("max-reward"));
                        $this->resetQuest($p);
                        $this->api->addMoney($p, $price);
                        $p->sendMessage("§l§aYou Succesfully Finish The Quest! Present Of This You Get {$price} Money!");
                    }
                }
            }
        }
    }
    
    public function addQuestUI(Player $p){
        $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
	    $form = new CustomForm(function (Player $p, $data = null) {
			if($data === null){
				return true;
			}
			$type = ["break", "place", "craft"][$data[0]];
			$item = StringToItemParser::getInstance()->parser((int)$data[1], (int)$data[2] ?? 0, (int)$data[3]);
			$this->addQuest($p, $type, $item);
        });
        $form->setTitle("§aAddNewQuest");
        $form->addDropdown("QuestType:", ["break", "place", "craft"]);
        $form->addInput("IdItem/Block", "example: 2");
        $form->addInput("MetaItem/Block", "example: 0");
        $form->addInput("AmountItem/Block", "example: 64");
        $p->sendForm($form);
        return $form;
    }
    
    public function deleteQuestUI(Player $p){
        $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = new SimpleForm(function (Player $p, $data = null) {
            if($data === null){
                return true;
            }             
            switch($data){
                case 0:
                    $this->deleteBreakQuest($p);
                break;
                case 1:
                    $this->deletePlaceQuest($p);
                break;
                case 2:
                    $this->deleteCraftQuest($p);
                break;
            }
        });
        $form->setTitle("§aDeleteQuest");
        $form->setContent("");
        $form->addButton("§eBreakType");
        $form->addButton("§ePlaceType");
        $form->addButton("§eCraftType");
        $form->sendToPlayer($p);
        return $form;
    }
    
    public function deleteBreakQuest(Player $p){
        $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = new SimpleForm(function (Player $p, $data = null) {
            if($data === null){
                return true;
            }             
            $this->deleteQuest($p, "break", (int)$data);
        });
        $form->setTitle("§aDeleteQuest");
        $form->setContent("");
        foreach($this->lg->getAll()["break"] as $dt){
            $form->addButton("§e{$dt}");
        }
        $form->sendToPlayer($p);
        return $form;
    }
    
    public function deletePlaceQuest(Player $p){
        $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = new SimpleForm(function (Player $p, $data = null) {
            if($data === null){
                return true;
            }             
            $this->deleteQuest($p, "place", (int)$data);
        });
        $form->setTitle("§aDeleteQuest");
        $form->setContent("");
        foreach($this->lg->getAll()["place"] as $dt){
            $form->addButton("§e{$dt}");
        }
        $form->sendToPlayer($p);
        return $form;
    }
    
    public function deleteCraftQuest(Player $p){
        $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = new SimpleForm(function (Player $p, $data = null) {
            if($data === null){
                return true;
            }             
            $this->deleteQuest($p, "craft", (int)$data);
        });
        $form->setTitle("§aDeleteQuest");
        $form->setContent("");
        foreach($this->lg->getAll()["craft"] as $dt){
            $form->addButton("§e{$dt}");
        }
        $form->sendToPlayer($p);
        return $form;
    }
    
    public function addQuest(Player $p, string $type, Item $item){
        $id = $item->getTypeId();
        $meta = $item->getMeta();
        $count = $item->getCount();
        if($type == "break" || $type == "place" || $type == "craft"){
            if($this->lg->getAll()[$type] == []){
                $this->lg->set($type, ["{$id}:{$meta}:{$count}"]);
                $this->lg->save();
                $p->sendMessage("§l§aAdded New Quest To {$type} Type!");
            }else{
                $arry = $this->lg->getAll()[$type];
                array_push($arry, "{$id}:{$meta}:{$count}");
                $this->lg->set($type, $arry);
                $this->lg->save();
                $p->sendMessage("§l§aAdded New Quest To {$type} Type!");
            }
        }else{
            $p->sendMessage("§l§eType Must Be One Of §b[break, place, or craft]");
        }
    }
    
    public function deleteQuest(Player $p, string $type, int $data){
        $rry = $this->lg->getAll()[$type];
        array_splice($rry, $data, 1);
        $this->lg->set($type, $rry);
        $this->lg->save();
        $p->sendMessage("§aSucces Delete Quest!");
    }
    
    public function sendQuest(Player $p){
        if($this->dt->getAll()[$p->getName()][0] !== "true"){
            $type = mt_rand(1, 3);
            switch($type){
                case 1:
                    $rand = $this->lg->getAll()["break"][array_rand($this->lg->getAll()["break"])];
                    $this->dt->set($p->getName(), ["true", "break", $rand, 0]);
                    $this->dt->save();
                    $x = explode(":", $rand);
                    $i = StringToItemParser::getInstance()->parser($x[0], $x[1], $x[2]);
                    $name = $i->getName();
                    $c = $i->getCount();
                    $p->sendMessage("§aQuest Started");
                    $p->sendMessage("§l§a>>> §bQuestInfo §l§a<<<\n§eType: §fBreakBlock\n§eInfo: §f{$name} {$c}x");
                break;
                case 2:
                    $rand = $this->lg->getAll()["place"][array_rand($this->lg->getAll()["place"])];
                    $this->dt->set($p->getName(), ["true", "place", $rand, 0]);
                    $this->dt->save();
                    $x = explode(":", $rand);
                    $i = StringToItemParser::getInstance()->parser($x[0], $x[1], $x[2]);
                    $name = $i->getName();
                    $c = $i->getCount();
                    $p->sendMessage("§aQuest Started");
                    $p->sendMessage("§l§a>>> §bQuestInfo §l§a<<<\n§eType: §fPlaceBlock\n§eInfo: §f{$name} {$c}x");
                break;
                case 3:
                    $rand = $this->lg->getAll()["craft"][array_rand($this->lg->getAll()["craft"])];
                    $this->dt->set($p->getName(), ["true", "craft", $rand, 0]);
                    $this->dt->save();
                    $x = explode(":", $rand);
                    $i = StringToItemParser::getInstance()->parser($x[0], $x[1], $x[2]);
                    $name = $i->getName();
                    $c = $i->getCount();
                    $p->sendMessage("§aQuest Started");
                    $p->sendMessage("§l§a>>> §bQuestInfo §l§a<<<\n§eType: §fCraftItem\n§eInfo: §f{$name} {$c}x");
                break;
            }
        }else{
            $p->sendMessage("§cYou Already Start Quest!");
        }
    }
    
    public function stopQuest(Player $p){
        if($this->dt->getAll()[$p->getName()][0] == "true"){
            $p->sendMessage("§cYou Quest Data Has Stopped And Can't Refund!");
            $this->dt->set($p->getName(), ["false", "null", "null", "null"]);
            $this->dt->save();
        }else{
            $p->sendMessage("§cYou Are Not Start Quest!");
        }
    }
    
    public function resetQuest(Player $p){
        $this->dt->set($p->getName(), ["false", "null", "null", "null"]);
        $this->dt->save();
    }
    
    public function sendQuestMenu(Player $p){
        $type = "";
        if($this->dt->getAll()[$p->getName()][0] == "true"){
            $type = "true";
        }else{
            $type = "false";
        }
        switch($type){
            case "true":
        $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = new SimpleForm(function (Player $p, $data = null) {
            if($data === null){
                return true;
            }             
            switch($data){
                case 0:
                    $this->stopQuest($p);
                break;
                case 1:
                    
                break;
            }
        });
        $qtype = $this->dt->getAll()[$p->getName()][1];
        $x = explode(":", $this->dt->getAll()[$p->getName()][2]);
        $name = StringToItemParser::getInstance()->parser($x[0], $x[1], $x[2])->getName();
        $c = $x[2];
        $form->setTitle("§l§bQuestMenu");
        $form->setContent("§aYou are working on a quest\n    §ahere is your quest information :\n\n§eQuestType: §f{$qtype}\n§eItem/Block Info: §f{$name} {$c}x");
        $form->addButton("§l§aStop Quest");
        $form->addButton("§l§cBack");
        $form->sendToPlayer($p);
        return $form;
            break;
            case "false":
        $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = new SimpleForm(function (Player $p, $data = null) {
            if($data === null){
                return true;
            }             
            switch($data){
                case 0:
                    $this->sendQuest($p);
                break;
                case 1:
                    
                break;
            }
        });
        $form->setTitle("§l§bQuestMenu");
        $form->setContent("");
        $form->addButton("§l§aStart Quest");
        $form->addButton("§l§cBack");
        $form->sendToPlayer($p);
        return $form;
            break;
        }
    }
}
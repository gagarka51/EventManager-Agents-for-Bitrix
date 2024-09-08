<?php

require __DIR__ . "/../modules/dev.site/lib/Handlers/Iblock.php";
require __DIR__ . "/../modules/dev.site/lib/Agents/Iblock.php";

use Bitrix\Main\EventManager;

// 3600 сек = 60 мин
CAgent::AddAgent(
	"Only\Site\Agents\Iblock::clearOldLogs();",
	"dev.site",  
	"Y", 
	3600,
	"Y"
);

EventManager::getInstance()->addEventHandler(
	"iblock",
	"OnAfterIBlockElementAdd",
	[
		"Iblock",
		"addLog"
	]
);

EventManager::getInstance()->addEventHandler(
	"iblock",
	"OnAfterIBlockElementUpdate",
	[
		"Iblock",
		"addLog"
	]
);
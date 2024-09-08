<?php

namespace Only\Site\Agents;


class Iblock
{
    public static function clearOldLogs()
    {
        $connection = \Bitrix\Main\Application::getConnection(); 
        $sqlHelper = $connection->getSqlHelper();

        $sql = "SELECT ID FROM `b_event_log` ORDER BY TIMESTAMP_X DESC LIMIT 10";
        $que = $connection->query($sql); 

        while ($result = $que->fetch()) {
            $arRes[] = $result;
        }

        $idSave = intval($arRes[9]["ID"]);
        $del = "DELETE FROM `b_event_log` WHERE `ID` < '" . $idSave . "'";
        $queDel = $connection->query($del);
    }

    public static function example()
    {
        /*global $DB;
        if (\Bitrix\Main\Loader::includeModule('iblock')) {
            $iblockId = \Only\Site\Helpers\IBlock::getIblockID('QUARRIES_SEARCH', 'SYSTEM');
            $format = $DB->DateFormatToPHP(\CLang::GetDateFormat('SHORT'));
            $rsLogs = \CIBlockElement::GetList(['TIMESTAMP_X' => 'ASC'], [
                'IBLOCK_ID' => $iblockId,
                '<TIMESTAMP_X' => date($format, strtotime('-1 months')),
            ], false, false, ['ID', 'IBLOCK_ID']);
            while ($arLog = $rsLogs->Fetch()) {
                \CIBlockElement::Delete($arLog['ID']);
            }
        }
        return '\\' . __CLASS__ . '::' . __FUNCTION__ . '();';*/
    }
}

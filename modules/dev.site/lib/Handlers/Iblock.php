<?php

//namespace Only\Site\Handlers;

class Iblock
{
    public static function addLog(&$arFields)
    {
        $CODE = "LOG";
        $type = "ambersite_conservativesite_s1";
        $site_id = "s1";

        if (!empty($arFields)) {
            $res = CIBlock::GetList(
                Array("SORT"=>"ASC"),
                Array("CODE" =>$CODE)
            );
            while($ar_res = $res->Fetch()) {
                $ar = $ar_res;
            }

            if (!($ar)) {
                $ib = new CIBlock;
                $arFieldsIb = Array(
                    "ACTIVE" => "Y",
                    "NAME" => "Логи",
                    "CODE" => $CODE,
                    "IBLOCK_TYPE_ID" => $type,
                    "SITE_ID" => $site_id
                );
                $ib->Add($arFieldsIb);
            }
             
            $result = CIBlock::GetByID(
                $arFields["IBLOCK_ID"]
            );

            if($ar_result = $result->GetNext())
                $arIb = $ar_result;

            $logIbSect = CIBlockSection::GetList(
                Array("SORT"=>"ASC"),
                Array(
                    "IBLOCK_ID" => $arIb["ID"],
                )
            );
            while($ar_log_ibSect = $logIbSect->GetNext()){
                $arLogIbSect[] = $ar_log_ibSect;
            }

            $nameSection = $ar_result["NAME"] . " " . $ar_result["ID"];

            $arFoundSect = self::findSect($nameSection);

            if (empty($arFoundSect)) {
                if ($arFields["IBLOCK_ID"] != $ar["ID"]) {
                    $bs = new CIBlockSection;
                    $bsFields = Array(
                        "ACTIVE" => "Y",
                        "NAME" => $nameSection,
                        "IBLOCK_ID" => $ar["ID"]
                    );
                    $bs->Add($bsFields);
                }
            }
            /*
             * Формируем анонс для элемента
             */ 

            if (empty($arLogIbSect)) {
                $elDir = "Без разделов->";
            } else {
                foreach ($arLogIbSect as $sect) {
                    if ($arFields["IBLOCK_SECTION"]) {
                        foreach ($arFields["IBLOCK_SECTION"] as $idIblSect) {
                            if ($idIblSect == $sect["ID"]) {
                                $elDir .= $sect["NAME"] . "->";
                            }
                        } 
                    } else {
                       $elDir = "Без разделов->"; 
                    }
                }
            }
            

            $findEl = CIBlockElement::GetList(
                Array("SORT"=>"ASC"),
                Array(
                    "IBLOCK_CODE" => "LOG",
                    "NAME" => $arFields["ID"]
                ),
            );
            while($ob = $findEl->GetNextElement()) {
                $arElems = $ob->GetFields();
            }

            if ($arFoundSect["NAME"] == $nameSection) {
                $el = new CIBlockElement;
                $arLoadProductArray = Array(
                    "MODIFIED_BY"    => $arFields["MODIFIED_BY"],
                    "IBLOCK_SECTION_ID" => $arFoundSect["ID"],
                    "IBLOCK_ID"      => $ar["ID"],
                    "NAME"           => $arFields["ID"],
                    "ACTIVE"         => "Y",
                    "ACTIVE_FROM" => $arFields["DATE_CREATE"],
                    "PREVIEW_TEXT"   => $arIb["NAME"] . " -> " . " $elDir " . $arFields["NAME"]
                );
                if (empty($arElems)) {
                    $el->Add($arLoadProductArray);
                }
                    $el->Update($arElems["ID"], $arLoadProductArray);   
            }
        }
    }

    static function findSect($nameSection)
    {
        if ($nameSection) {
            $ibSect = CIBlockSection::GetList(
                Array("SORT"=>"ASC"),
                Array(
                    "NAME" => $nameSection,
                    "IBLOCK_ID" => $ar["ID"]
                )
            );
            while($ar_ibSect = $ibSect->GetNext()){
                $arIbSect = $ar_ibSect;
            }

            return $arIbSect;
        }
    }

    function OnBeforeIBlockElementAddHandler(&$arFields)
    {
        $iQuality = 95;
        $iWidth = 1000;
        $iHeight = 1000;
        /*
         * Получаем пользовательские свойства
         */
        $dbIblockProps = \Bitrix\Iblock\PropertyTable::getList(array(
            'select' => array('*'),
            'filter' => array('IBLOCK_ID' => $arFields['IBLOCK_ID'])
        ));
        /*
         * Выбираем только свойства типа ФАЙЛ (F)
         */
        $arUserFields = [];
        while ($arIblockProps = $dbIblockProps->Fetch()) {
            if ($arIblockProps['PROPERTY_TYPE'] == 'F') {
                $arUserFields[] = $arIblockProps['ID'];
            }
        }
        /*
         * Перебираем и масштабируем изображения
         */
        foreach ($arUserFields as $iFieldId) {
            foreach ($arFields['PROPERTY_VALUES'][$iFieldId] as &$file) {
                if (!empty($file['VALUE']['tmp_name'])) {
                    $sTempName = $file['VALUE']['tmp_name'] . '_temp';
                    $res = \CAllFile::ResizeImageFile(
                        $file['VALUE']['tmp_name'],
                        $sTempName,
                        array("width" => $iWidth, "height" => $iHeight),
                        BX_RESIZE_IMAGE_PROPORTIONAL_ALT,
                        false,
                        $iQuality);
                    if ($res) {
                        rename($sTempName, $file['VALUE']['tmp_name']);
                    }
                }
            }
        }

        if ($arFields['CODE'] == 'brochures') {
            $RU_IBLOCK_ID = \Only\Site\Helpers\IBlock::getIblockID('DOCUMENTS', 'CONTENT_RU');
            $EN_IBLOCK_ID = \Only\Site\Helpers\IBlock::getIblockID('DOCUMENTS', 'CONTENT_EN');
            if ($arFields['IBLOCK_ID'] == $RU_IBLOCK_ID || $arFields['IBLOCK_ID'] == $EN_IBLOCK_ID) {
                \CModule::IncludeModule('iblock');
                $arFiles = [];
                foreach ($arFields['PROPERTY_VALUES'] as $id => &$arValues) {
                    $arProp = \CIBlockProperty::GetByID($id, $arFields['IBLOCK_ID'])->Fetch();
                    if ($arProp['PROPERTY_TYPE'] == 'F' && $arProp['CODE'] == 'FILE') {
                        $key_index = 0;
                        while (isset($arValues['n' . $key_index])) {
                            $arFiles[] = $arValues['n' . $key_index++];
                        }
                    } elseif ($arProp['PROPERTY_TYPE'] == 'L' && $arProp['CODE'] == 'OTHER_LANG' && $arValues[0]['VALUE']) {
                        $arValues[0]['VALUE'] = null;
                        if (!empty($arFiles)) {
                            $OTHER_IBLOCK_ID = $RU_IBLOCK_ID == $arFields['IBLOCK_ID'] ? $EN_IBLOCK_ID : $RU_IBLOCK_ID;
                            $arOtherElement = \CIBlockElement::GetList([],
                                [
                                    'IBLOCK_ID' => $OTHER_IBLOCK_ID,
                                    'CODE' => $arFields['CODE']
                                ], false, false, ['ID'])
                                ->Fetch();
                            if ($arOtherElement) {
                                /** @noinspection PhpDynamicAsStaticMethodCallInspection */
                                \CIBlockElement::SetPropertyValues($arOtherElement['ID'], $OTHER_IBLOCK_ID, $arFiles, 'FILE');
                            }
                        }
                    } elseif ($arProp['PROPERTY_TYPE'] == 'E') {
                        $elementIds = [];
                        foreach ($arValues as &$arValue) {
                            if ($arValue['VALUE']) {
                                $elementIds[] = $arValue['VALUE'];
                                $arValue['VALUE'] = null;
                            }
                        }
                        if (!empty($arFiles && !empty($elementIds))) {
                            $rsElement = \CIBlockElement::GetList([],
                                [
                                    'IBLOCK_ID' => \Only\Site\Helpers\IBlock::getIblockID('PRODUCTS', 'CATALOG_' . $RU_IBLOCK_ID == $arFields['IBLOCK_ID'] ? '_RU' : '_EN'),
                                    'ID' => $elementIds
                                ], false, false, ['ID', 'IBLOCK_ID', 'NAME']);
                            while ($arElement = $rsElement->Fetch()) {
                                /** @noinspection PhpDynamicAsStaticMethodCallInspection */
                                \CIBlockElement::SetPropertyValues($arElement['ID'], $arElement['IBLOCK_ID'], $arFiles, 'FILE');
                            }
                        }
                    }
                }
            }
        }
    }

}

<?php

use Db\Where;
use Models\LangsModel;

require_once 'include.php';
$langs = LangsModel::select();

$en_langs = LangsModel::select(Where::equal('lang', 'en'), false);
$id_langs = LangsModel::select(Where::equal('lang', 'id'), false);
$ru_langs = LangsModel::select(Where::equal('lang', 'ru'), false);
$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

$worksheet = $spreadsheet->setActiveSheetIndex(0);

$worksheet
    ->setCellValue('A1', 'Key')
    ->setCellValue('B1', 'En')
    ->setCellValue('C1', 'Id')
    ->setCellValue('D1', 'Ru');

$i = 2;
foreach ($langs as $lang) {
    /** @var LangsModel $lang */

    $id_lang = $id_langs->filter(function($item) use ($lang){
        /** @var LangsModel $item */
        return $item->name === $lang->name;
    });

    $ru_lang = $ru_langs->filter(function($item) use ($lang){
        /** @var LangsModel $item */
        return $item->name === $lang->name;
    });


    $worksheet->setCellValue("A{$i}", $lang->name);
    $worksheet->setCellValue("B{$i}", $lang->value);
    try {
        /** @var LangsModel $id_lang */
        $id_lang = $id_lang->first();
    } catch (\Db\Model\Exception\ModelNotFoundException $e) {
        $id_lang = null;
    }

    try {
        /** @var LangsModel $id_lang */
        $ru_lang = $ru_lang->first();
    } catch (\Db\Model\Exception\ModelNotFoundException $e) {
        $ru_lang = null;
    }

    if ($id_lang) {
        $worksheet->setCellValue("C{$i}", $id_lang->value);
    }

    if ($ru_lang) {
        $worksheet->setCellValue("D{$i}", $ru_lang->value);
    }

    $i++;
}

$active_sheet = $spreadsheet->getActiveSheet();
$active_sheet->getColumnDimension('C')->setWidth(60);
$active_sheet->getColumnDimension('B')->setWidth(60);
$active_sheet->getColumnDimension('D')->setWidth(60);
$active_sheet->freezePane('D2');

$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
$writer->save("langs.xlsx");

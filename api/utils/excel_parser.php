<?php

require_once '../include.php';

$excel = \PhpOffice\PhpSpreadsheet\IOFactory::load('langs_ready.xlsx');
$sheet = $excel->getActiveSheet();
$data = [];
for ($row = 2; $row < $sheet->getHighestRow(); $row++) {
    $key = $sheet->getCell("A{$row}")->getValue();
    if ($key) {
        $data[] = [
            'name' => $key,
            'values' => [
                'en' => $sheet->getCell("B{$row}")->getValue(),
                'ru' => $sheet->getCell("C{$row}")->getValue(),
                'id' => $sheet->getCell("D{$row}")->getValue()
            ]
        ];
    }
}

foreach ($data as $item) {
    foreach ($item['values'] as $lang => $value) {
        if (!$value) {
            continue;
        }

        $model = \Models\LangsModel::first(
            \Db\Where::and()
            ->set(\Db\Where::equal('lang', $lang))
            ->set(\Db\Where::equal('name', $item['name']))
        );

        if ($model) {
            $model->value = $value;
            $model->save();
        } else {
            $model = new \Models\LangsModel();
            $model->name = $item['name'];
            $model->lang = $lang;
            $model->value = $value;
            $model->type = \Models\LangsModel::BACKEND_LANG;
            $model->save();
        }
    }
}

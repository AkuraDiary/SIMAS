<?php
require 'vendor/autoload.php';
$phpWord = new \PhpOffice\PhpWord\PhpWord();
$section = $phpWord->addSection();
$table = $section->addTable();
$table->addRow();
$table->addCell(2000)->addText('No');
$table->addCell(2000)->addText('Nama');
$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
$objWriter->save('test_table.html');
echo file_get_contents('test_table.html');

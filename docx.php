<?php
error_reporting(E_ERROR);
ini_set('display_errors', true);


require 'dompdf/vendor/autoload.php';

use Dompdf\Dompdf;

$dompdf = new Dompdf();
$content = '';
$content .= '<link href="resources/quill.snow.css" rel="stylesheet">';
$content .= '<link href="resources/dompdf.css" rel="stylesheet">';

//cjk fonts support
$html = file_get_contents('data.html');
// $cjk_scripts = 'Bopomofo|Han|Hiragana|Katakana';
// $cjk_scripts = preg_replace('/[a-zA-Z_]+/', '\\p{$0}', $cjk_scripts);
// $html = preg_replace("/($cjk_scripts)+/isu", '<span class="cjk">$0</span>', $html);
$html = preg_replace("/[\x{3000}-\x{ffff}]+/iu", '<span class="cjk">$0</span>', $html);
$content .= $html;

$dompdf->loadHtml($content);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('sample.pdf', array('Attachment' => false));

exit();


require 'html2pdf/vendor/autoload.php';

use Spipu\Html2Pdf\Html2Pdf;

$html2pdf = new Html2Pdf('P','A4','en', false, 'UTF-8', array('17', '17', '17', '17'));
$content = file_get_contents('data.html');
$html2pdf->writeHTML($content);
$html2pdf->output();

exit();


require 'phpword/vendor/autoload.php';
$phpWord = new \PhpOffice\PhpWord\PhpWord();
$section = $phpWord->addSection();

$content = '<link href="resources/quill.snow.css" rel="stylesheet">';
$content .= file_get_contents('data.html');
\PhpOffice\PhpWord\Shared\Html::addHtml($section, $content);

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment;filename="test.docx"');

$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save('php://output');

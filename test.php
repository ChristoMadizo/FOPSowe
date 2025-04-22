<?php
require_once 'vendor/setasign/fpdf/fpdf.php';
require_once 'vendor/setasign/fpdi/src/autoload.php';

use setasign\Fpdi\Fpdi;

$pdf = new Fpdi();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(40, 10, 'Hello FPDI!');
$pdf->Output();
?>

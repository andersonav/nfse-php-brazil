<?php
declare(strict_types=1);
namespace Alves\NfseBrasil\Danfse;

final class DanfseCanvas extends \FPDF
{
    public function __construct($orientation = 'P', $unit = 'mm', $size = 'A4')
    {
        parent::__construct($orientation, $unit, $size);
        $this->fontpath = __DIR__ . '/assets/fonts/';
    }

    public function watermark(string $text): void
    {
        $angle = 45 * M_PI / 180;
        $c = cos($angle); $s = sin($angle);
        $cx = 105 * $this->k; $cy = (297 - 148.5) * $this->k;
        $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.5F %.5F cm 1 0 0 1 %.5F %.5F cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
        $this->SetFont('DanfseArial', '', 50);
        $this->SetTextColor(166, 166, 166);
        $this->SetXY(15, 139);
        $this->Cell(180, 20, iconv('UTF-8', 'Windows-1252', $text), 0, 0, 'C');
        $this->_out('Q');
        $this->SetTextColor(0, 0, 0);
    }
}

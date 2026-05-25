<?php
declare(strict_types=1);

namespace Alves\NfseBrasil\Danfse;

use Alves\NfseBrasil\Utils\NfseCodigoPadrao;
use DateTimeImmutable;
use DOMDocument;
use DOMXPath;
use FPDF;
use RuntimeException;
use TCPDF2DBarcode;

final class DanfsePdfGenerator
{
    /** @var array<int,array{nome:string,uf:string}>|null */
    private static $municipiosCache = null;

    public function generate(string $nfseXml, $provider = null): string
    {
        if (!class_exists(FPDF::class)) {
            throw new RuntimeException('FPDF nao encontrado.');
        }

        $d = $this->extractData($nfseXml);

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(6, 6, 6);
        $pdf->SetAutoPageBreak(true, 6);
        $pdf->AddPage();

        $x = 6.0;
        $w = 198.0;
        $y = 6.0;

        $y = $this->drawHeader($pdf, $d, $x, $y, $w);
        $y = $this->sectionPrestadorTomador($pdf, $d, $x, $y, $w);
        $y = $this->sectionServico($pdf, $d, $x, $y, $w);
        $y = $this->sectionTributacaoMunicipalFederal($pdf, $d, $x, $y, $w);
        $y = $this->sectionTributacaoIbscbs($pdf, $d, $x, $y, $w);
        $y = $this->sectionTotais($pdf, $d, $x, $y, $w);
        $y = $this->sectionInfo($pdf, $d, $x, $y, $w);
        $this->sectionCanhoto($pdf, $d, $x, $y, $w);

        return $pdf->Output('S');
    }

    private function drawHeader(FPDF $pdf, array $d, float $x, float $y, float $w): float
    {
        $h = 46.0;
        $pdf->Rect($x, $y, $w, $h);

        $logo = __DIR__ . '/assets/img/logo-nfs-e-horizontal.png';
        if (is_file($logo)) {
            $pdf->Image($logo, $x + 1.5, $y + 1.5, 38);
        }

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetXY($x, $y + 2);
        $pdf->Cell($w, 5, $this->txt('DANFSe v2.0'), 0, 0, 'C');
        $pdf->SetXY($x, $y + 7);
        $pdf->Cell($w, 5, $this->txt('Documento Auxiliar da NFS-e'), 0, 0, 'C');
        if (strpos($d['ambiente_desc'], '2 - Homologação') !== false) {
            $pdf->SetTextColor(180, 0, 0);
            $pdf->SetFont('Arial', 'B', 7.5);
            $pdf->SetXY($x, $y + 10.6);
            $pdf->Cell($w, 3.5, $this->txt('NFS-e SEM VALIDADE JURÍDICA'), 0, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
        }

        $pdf->SetFont('Arial', '', 7);
        $pdf->SetXY($x + 148, $y + 2);
        $pdf->MultiCell(
            49,
            3.3,
            $this->txt('Município: ') . $this->safe($d['municipio_label']) . "\n"
            . $this->txt('Ambiente Gerador: ') . $this->safe($d['ambiente_gerador_desc']) . "\n"
            . $this->txt('Tipo de Ambiente: ') . $this->safe($d['ambiente_desc']),
            0,
            'L'
        );

        $pdf->Line($x, $y + 14, $x + $w, $y + 14);
        $pdf->SetFont('Arial', 'B', 7.2);
        $pdf->SetXY($x + 2, $y + 15.2);
        $pdf->Cell($w - 4, 3.8, $this->txt('CHAVE DE ACESSO DA NFS-e'), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 6.7);
        $pdf->SetXY($x + 2, $y + 19);
        $pdf->Cell($w - 4, 3.2, $this->fit($pdf, $d['chave'], $w - 6), 0, 0, 'L');

        $gridY = $y + 23.0;
        $gridH = 24.0;
        $pdf->Rect($x, $gridY, $w, $gridH);

        $colW = $w / 4.0;
        $rowH = $gridH / 3.0;
        for ($i = 1; $i < 4; $i++) {
            $pdf->Line($x + ($colW * $i), $gridY, $x + ($colW * $i), $gridY + $gridH);
        }
        for ($i = 1; $i < 3; $i++) {
            $pdf->Line($x, $gridY + ($rowH * $i), $x + (3 * $colW), $gridY + ($rowH * $i));
        }

        $rows = [
            [
                ['NÚMERO DA NFS-e', $d['numero_nfse']],
                ['COMPETÊNCIA DA NFS-e', $d['competencia']],
                ['DATA E HORA DA EMISSÃO DA NFS-e', $d['data_emissao_nfse']],
            ],
            [
                ['NÚMERO DA DPS', $d['numero_rps']],
                ['SÉRIE DA DPS', $d['serie_rps']],
                ['DATA E HORA DA EMISSÃO DA DPS', $d['data_emissao_dps']],
            ],
            [
                ['EMITENTE DA NFS-e', $d['emit_nome']],
                ['SITUAÇÃO DA NFS-e', $d['situacao_nfse_desc']],
                ['FINALIDADE', $d['finalidade_desc']],
            ],
        ];

        foreach ($rows as $r => $cells) {
            foreach ($cells as $c => $cell) {
                $cx = $x + ($c * $colW);
                $cy = $gridY + ($r * $rowH);
                $pdf->SetFont('Arial', 'B', 6.4);
                $pdf->SetXY($cx + 1, $cy + 0.7);
                $pdf->Cell($colW - 2, 2.7, $this->txt($cell[0]), 0, 0, 'L');
                $pdf->SetFont('Arial', '', 6.2);
                $pdf->SetXY($cx + 1, $cy + 3.4);
                $pdf->Cell($colW - 2, 2.7, $this->fit($pdf, (string) $cell[1], $colW - 2), 0, 0, 'L');
            }
        }

        $qrX = $x + (3 * $colW);
        $qrY = $gridY;
        $this->drawQrCode($pdf, $d['qrcode_payload'], $qrX + (($colW - 14.0) / 2), $qrY + 1.0, 14.0);
        $pdf->SetFont('Arial', '', 6.0);
        $pdf->SetXY($qrX + 1.0, $qrY + 15.8);
        $pdf->MultiCell(
            $colW - 2.0,
            2.3,
            $this->txt('A autenticidade pode ser verificada pelo QR Code ou pela chave de acesso no portal da NFS-e.'),
            0,
            'C'
        );

        return $y + $h;
    }

    private function sectionPrestadorTomador(FPDF $pdf, array $d, float $x, float $y, float $w): float
    {
        $h = 62.0;
        $pdf->Rect($x, $y, $w, $h);

        $this->bar($pdf, $x, $y, $w, 'PRESTADOR / FORNECEDOR');
        $this->line1($pdf, $x, $y + 4, $w, 'Nome / Nome Empresarial', $d['emit_nome']);
        $this->line4($pdf, $x, $y + 10.8, $w, ['CNPJ / CPF / NIF', 'Indicador Municipal (Inscrição)', 'Telefone', 'E-mail'], [$d['emit_doc'], $d['emit_im'], $d['emit_tel'], $d['emit_email']]);
        $this->lineAddressSidebar($pdf, $x, $y + 17.6, $w, 'Endereço', $d['emit_end'], 'Município / Sigla UF', $d['emit_mun_uf'], 'Código IBGE / CEP', $d['emit_ibge_cep']);
        $this->line4($pdf, $x, $y + 27.2, $w, ['Simples Nacional na Data de Competência', 'Regime de Apuração Tributária pelo SN', '', ''], [$d['sn_comp_desc'], $d['reg_ap_sn_desc'], '', '']);

        $this->bar($pdf, $x, $y + 34.0, $w, 'TOMADOR / ADQUIRENTE');
        $this->line1($pdf, $x, $y + 38.0, $w, 'Nome / Nome Empresarial', $d['toma_nome']);
        $this->line4($pdf, $x, $y + 44.8, $w, ['CNPJ / CPF / NIF', 'Indicador Municipal (Inscrição)', 'Telefone', 'E-mail'], [$d['toma_doc'], $d['toma_im'], $d['toma_tel'], $d['toma_email']]);
        $this->lineAddressSidebar($pdf, $x, $y + 51.6, $w, 'Endereço', $d['toma_end'], 'Município / Sigla UF', $d['toma_mun_uf'], 'Código IBGE / CEP', $d['toma_ibge_cep']);

        return $y + $h;
    }

    private function sectionServico(FPDF $pdf, array $d, float $x, float $y, float $w): float
    {
        $h = 52.0;
        $pdf->Rect($x, $y, $w, $h);
        $this->bar($pdf, $x, $y, $w, 'SERVIÇO PRESTADO');

        $pdf->SetFont('Arial', 'B', 6.3);
        $pdf->SetXY($x + 1, $y + 4.2);
        $pdf->Cell($w - 2, 2.8, $this->txt('Código de Tributação Nacional / Municipal'), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 6.2);
        $pdf->SetXY($x + 1, $y + 7.1);
        $codigoTrib = $this->safe(trim($d['trib_nac'] . ' / ' . $d['trib_mun']));
        $pdf->MultiCell($w - 2, 2.7, $codigoTrib, 0, 'L');
        $afterTribY = (float) $pdf->GetY();

        $colW = $w / 2.0;
        $rowY = max($y + 14.0, $afterTribY + 1.4);
        $rowH = 6.8;
        $pdf->Line($x + $colW, $rowY - 0.2, $x + $colW, $rowY + $rowH);

        $pdf->SetFont('Arial', 'B', 6.3);
        $pdf->SetXY($x + 1, $rowY + 0.2);
        $pdf->Cell($colW - 2, 2.8, $this->txt('Código da NBS'), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 6.2);
        $pdf->SetXY($x + 1, $rowY + 3.1);
        $pdf->Cell($colW - 2, 2.8, $this->fit($pdf, $d['nbs'], $colW - 2), 0, 0, 'L');

        $pdf->SetFont('Arial', 'B', 6.3);
        $pdf->SetXY($x + $colW + 1, $rowY + 0.2);
        $pdf->Cell($colW - 2, 2.8, $this->txt('Local da Prestação / Sigla UF / País'), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 6.2);
        $pdf->SetXY($x + $colW + 1, $rowY + 3.1);
        $pdf->Cell($colW - 2, 2.8, $this->fit($pdf, $d['local_prest_desc'], $colW - 2), 0, 0, 'L');

        $descStartY = $rowY + $rowH + 2.0;
        $pdf->SetFont('Arial', 'B', 6.3);
        $pdf->SetXY($x + 1, $descStartY);
        $pdf->Cell($w - 2, 2.8, $this->txt('Descrição do Serviço'), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 6.1);
        $pdf->SetXY($x + 1, $descStartY + 3.1);
        $pdf->MultiCell($w - 2, 2.7, $this->safe($d['serv_desc']), 0, 'L');

        return $y + $h;
    }

    private function line1(FPDF $pdf, float $x, float $y, float $w, string $label, string $value): void
    {
        $pdf->SetFont('Arial', 'B', 6.3);
        $pdf->SetXY($x + 1, $y);
        $pdf->Cell($w - 2, 2.8, $this->fit($pdf, $label, $w - 2), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 6.2);
        $pdf->SetXY($x + 1, $y + 2.9);
        $pdf->Cell($w - 2, 2.8, $this->fit($pdf, $value, $w - 2), 0, 0, 'L');
    }

    private function sectionTributacaoMunicipalFederal(FPDF $pdf, array $d, float $x, float $y, float $w): float
    {
        $h = 34.5;
        $pdf->Rect($x, $y, $w, $h);
        $this->bar($pdf, $x, $y, $w, 'TRIBUTAÇÃO MUNICIPAL (ISSQN)');
        $this->line4($pdf, $x, $y + 4, $w, ['Regime Especial de Tributação do ISSQN', 'Tipo de Tributação do ISSQN', 'Município / Sigla UF / País de Incidência do ISSQN', 'Número Processo Suspensão'], [$d['reg_esp_issqn_desc'], $d['trib_issqn_desc'], $d['mun_incid'], $d['proc_susp']]);
        $this->line4($pdf, $x, $y + 10.8, $w, ['Benefício Municipal', 'Cálculo do BM', 'Total Deduções/Reduções', 'Desconto Incondicionado'], [$d['benef_mun'], $d['calc_bm'], $d['ded_red'], $d['desc_incond']]);
        $this->line4($pdf, $x, $y + 17.6, $w, ['BC ISSQN', 'Alíquota Aplicada', 'Retenção do ISSQN', 'ISSQN Apurado'], [$d['bc_issqn'], $d['aliq'], $d['ret_issqn_desc'], $d['v_issqn']]);

        $this->bar($pdf, $x, $y + 24.2, $w, 'TRIBUTAÇÃO FEDERAL (EXCETO CBS)');
        $this->line4($pdf, $x, $y + 28.2, $w, ['IRRF', 'Contribuição Previdenciária - Retida', 'Contribuições Sociais - Retidas', 'PIS / COFINS - Débito Apuração Própria'], [$d['v_irrf'], $d['v_prev'], $d['v_soc'], $this->fitPair($d['v_pis'], $d['v_cofins'])]);

        return $y + $h;
    }

    private function sectionTributacaoIbscbs(FPDF $pdf, array $d, float $x, float $y, float $w): float
    {
        $h = 19.0;
        $pdf->Rect($x, $y, $w, $h);
        $this->bar($pdf, $x, $y, $w, 'TRIBUTAÇÃO IBS / CBS');
        $this->line4($pdf, $x, $y + 4, $w, ['Exclusões e Reduções da Base de Cálculo', 'Base de Cálculo Após Exclusões e Reduções', 'Red. Alíquota IBS / Red. Alíquota CBS', 'Alíquota IBS UF / IBS Mun'], [$d['ibs_exclusoes'], $d['ibs_base_apos'], $d['ibs_red_aliq'], $d['ibs_aliq_uf_mun']]);
        $this->line4($pdf, $x, $y + 10.8, $w, ['Alíquota Efetiva Municipal – IBS', 'Valor Apurado Municipal – IBS', 'Alíquota Efetiva Estadual – IBS', 'Valor Apurado Estadual – IBS'], [$d['ibs_aliq_mun'], $d['ibs_valor_mun'], $d['ibs_aliq_est'], $d['ibs_valor_est']]);
        return $y + $h;
    }

    private function sectionTotais(FPDF $pdf, array $d, float $x, float $y, float $w): float
    {
        $h = 18.0;
        $pdf->Rect($x, $y, $w, $h);
        $this->bar($pdf, $x, $y, $w, 'VALOR TOTAL DA NFS-e');
        $this->line4($pdf, $x, $y + 4, $w, ['Valor da Operação / Serviço', 'Desconto Incondicionado', 'Desconto Condicionado', 'Valor Líquido da NFS-e + IBS/CBS'], [$d['v_serv'], $d['desc_incond'], $d['desc_cond'], $d['v_liq_ibscbs']]);
        $this->line4($pdf, $x, $y + 10.8, $w, ['Total das Retenções (ISSQN / Federais)', 'VALOR LÍQUIDO DA NFS-e', 'Total do IBS/CBS', ''], [$d['ret_total'], $d['v_liq'], $d['v_ibscbs'], '']);
        return $y + $h;
    }

    private function sectionInfo(FPDF $pdf, array $d, float $x, float $y, float $w): float
    {
        $h = 26.0;
        $pdf->Rect($x, $y, $w, $h);
        $this->bar($pdf, $x, $y, $w, 'INFORMAÇÕES COMPLEMENTARES');
        $pdf->SetFont('Arial', '', 7.0);
        $pdf->SetXY($x + 1, $y + 5.2);
        $pdf->MultiCell($w - 2, 3.4, $this->safe($d['info_comp']), 0, 'L');
        return $y + $h;
    }

    private function sectionCanhoto(FPDF $pdf, array $d, float $x, float $y, float $w): float
    {
        $h = 11.0;
        $pdf->Rect($x, $y, $w, $h);
        $col1 = $w * 0.22;
        $col2 = $w * 0.22;
        $col3 = $w - $col1 - $col2;
        $pdf->Line($x + $col1, $y, $x + $col1, $y + $h);
        $pdf->Line($x + $col1 + $col2, $y, $x + $col1 + $col2, $y + $h);

        $pdf->SetFont('Arial', 'B', 7.2);
        $pdf->SetXY($x + 1, $y + 1.0);
        $pdf->Cell($col1 - 2, 3.0, $this->txt('DATA CIENTIFICAÇÃO:'), 0, 0, 'L');
        $pdf->SetXY($x + $col1 + 1, $y + 1.0);
        $pdf->Cell($col2 - 2, 3.0, $this->txt('IDENTIFICAÇÃO E ASSINATURA'), 0, 0, 'L');
        $pdf->SetXY($x + $col1 + $col2 + 1, $y + 1.0);
        $pdf->Cell($col3 - 2, 3.0, $this->txt('Nº NFS-e / CHAVE NFS-e'), 0, 0, 'L');

        $pdf->SetFont('Arial', '', 7.0);
        $pdf->SetXY($x + $col1 + $col2 + 1, $y + 4.6);
        $pdf->Cell($col3 - 2, 3.0, $this->fit($pdf, $d['numero_nfse'] . ' / ' . $d['chave'], $col3 - 2), 0, 0, 'L');
        return $y + $h;
    }

    private function line4(FPDF $pdf, float $x, float $y, float $w, array $labels, array $values): void
    {
        $colW = $w / 4.0;
        for ($i = 1; $i < 4; $i++) {
            $pdf->Line($x + ($colW * $i), $y - 0.4, $x + ($colW * $i), $y + 6.3);
        }
        for ($i = 0; $i < 4; $i++) {
            $cx = $x + ($i * $colW);
            $label = (string) ($labels[$i] ?? '');
            $value = (string) ($values[$i] ?? '');
            if (trim($label) === '') {
                continue;
            }
            $pdf->SetFont('Arial', 'B', 6.3);
            $pdf->SetXY($cx + 1, $y);
            $pdf->Cell($colW - 2, 2.8, $this->fit($pdf, $label, $colW - 2), 0, 0, 'L');
            $pdf->SetFont('Arial', '', 6.2);
            $pdf->SetXY($cx + 1, $y + 2.9);
            $pdf->Cell($colW - 2, 2.8, $this->fit($pdf, $value, $colW - 2), 0, 0, 'L');
        }
    }

    private function line3(FPDF $pdf, float $x, float $y, float $w, array $labels, array $values): void
    {
        $colW = $w / 3.0;
        for ($i = 1; $i < 3; $i++) {
            $pdf->Line($x + ($colW * $i), $y - 0.4, $x + ($colW * $i), $y + 6.3);
        }
        for ($i = 0; $i < 3; $i++) {
            $cx = $x + ($i * $colW);
            $label = (string) ($labels[$i] ?? '');
            $value = (string) ($values[$i] ?? '');
            if (trim($label) === '') {
                continue;
            }
            $pdf->SetFont('Arial', 'B', 6.3);
            $pdf->SetXY($cx + 1, $y);
            $pdf->Cell($colW - 2, 2.8, $this->fit($pdf, $label, $colW - 2), 0, 0, 'L');
            $pdf->SetFont('Arial', '', 6.2);
            $pdf->SetXY($cx + 1, $y + 2.9);
            $pdf->Cell($colW - 2, 2.8, $this->fit($pdf, $value, $colW - 2), 0, 0, 'L');
        }
    }

    private function lineAddressSidebar(
        FPDF $pdf,
        float $x,
        float $y,
        float $w,
        string $leftLabel,
        string $leftValue,
        string $topRightLabel,
        string $topRightValue,
        string $bottomRightLabel,
        string $bottomRightValue
    ): void {
        $colW = $w / 4.0;
        $leftW = $colW * 2;
        $midW = $colW;
        $rightW = $colW;

        $pdf->Line($x + $leftW, $y - 0.4, $x + $leftW, $y + 6.3);
        $pdf->Line($x + $leftW + $midW, $y - 0.4, $x + $leftW + $midW, $y + 6.3);

        $pdf->SetFont('Arial', 'B', 6.3);
        $pdf->SetXY($x + 1, $y);
        $pdf->Cell($leftW - 2, 2.8, $this->fit($pdf, $leftLabel, $leftW - 2), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 6.2);
        $pdf->SetXY($x + 1, $y + 2.9);
        $pdf->Cell($leftW - 2, 2.8, $this->fit($pdf, $leftValue, $leftW - 2), 0, 0, 'L');

        $pdf->SetFont('Arial', 'B', 6.3);
        $pdf->SetXY($x + $leftW + 1, $y);
        $pdf->Cell($midW - 2, 2.8, $this->fit($pdf, $topRightLabel, $midW - 2), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 6.2);
        $pdf->SetXY($x + $leftW + 1, $y + 2.9);
        $pdf->Cell($midW - 2, 2.8, $this->fit($pdf, $topRightValue, $midW - 2), 0, 0, 'L');

        $pdf->SetFont('Arial', 'B', 6.3);
        $pdf->SetXY($x + $leftW + $midW + 1, $y);
        $pdf->Cell($rightW - 2, 2.8, $this->fit($pdf, $bottomRightLabel, $rightW - 2), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 6.2);
        $pdf->SetXY($x + $leftW + $midW + 1, $y + 2.9);
        $pdf->Cell($rightW - 2, 2.8, $this->fit($pdf, $bottomRightValue, $rightW - 2), 0, 0, 'L');
    }

    private function bar(FPDF $pdf, float $x, float $y, float $w, string $title): void
    {
        $pdf->SetFillColor(232, 232, 232);
        $pdf->Rect($x, $y, $w, 4, 'F');
        $pdf->SetFont('Arial', 'B', 7.4);
        $pdf->SetXY($x + 1, $y + 0.6);
        $pdf->Cell($w - 2, 3, $this->txt($title), 0, 0, 'L');
    }

    private function drawQrCode(FPDF $pdf, string $payload, float $x, float $y, float $size): void
    {
        $payload = trim($payload);
        if ($payload === '') {
            return;
        }
        if (!class_exists(TCPDF2DBarcode::class)) {
            $barcodeLib = dirname(__DIR__, 2) . '/vendor/tecnickcom/tcpdf/tcpdf_barcodes_2d.php';
            if (is_file($barcodeLib)) {
                require_once $barcodeLib;
            }
        }
        if (!class_exists(TCPDF2DBarcode::class)) {
            return;
        }
        $qr = new TCPDF2DBarcode($payload, 'QRCODE,M');

        // Preferência: raster PNG (quando GD/Imagick está disponível).
        $tmp = tempnam(sys_get_temp_dir(), 'qr_');
        if ($tmp !== false) {
            $pngPath = $tmp . '.png';
            @unlink($tmp);
            try {
                $png = $qr->getBarcodePngData(3, 3, [0, 0, 0]);
                if (is_string($png) && $png !== '') {
                    file_put_contents($pngPath, $png);
                    if (is_file($pngPath)) {
                        $pdf->Image($pngPath, $x, $y, $size, $size, 'PNG');
                        return;
                    }
                }
            } finally {
                if (isset($pngPath) && is_file($pngPath)) {
                    @unlink($pngPath);
                }
            }
        }

        // Fallback robusto: desenha o QR diretamente no PDF sem depender de GD/Imagick.
        $arr = $qr->getBarcodeArray();
        if (!isset($arr['num_rows'], $arr['num_cols'], $arr['bcode'])) {
            return;
        }
        $rows = (int) $arr['num_rows'];
        $cols = (int) $arr['num_cols'];
        if ($rows <= 0 || $cols <= 0) {
            return;
        }

        $module = $size / (float) max($rows, $cols);
        $offsetX = $x + (($size - ($cols * $module)) / 2);
        $offsetY = $y + (($size - ($rows * $module)) / 2);

        $pdf->SetFillColor(0, 0, 0);
        for ($r = 0; $r < $rows; $r++) {
            for ($c = 0; $c < $cols; $c++) {
                if (!empty($arr['bcode'][$r][$c])) {
                    $pdf->Rect(
                        $offsetX + ($c * $module),
                        $offsetY + ($r * $module),
                        $module,
                        $module,
                        'F'
                    );
                }
            }
        }
    }

    private function extractData(string $xml): array
    {
        $dom = new DOMDocument();
        if (!@$dom->loadXML($xml)) {
            throw new RuntimeException('Não foi possivel interpretar o XML da NFS-e.');
        }
        $xp = new DOMXPath($dom);
        $v = static function (array $queries) use ($xp): string {
            foreach ($queries as $query) {
                $raw = @$xp->evaluate("string({$query})");
                $value = trim((string) $raw);
                if ($value !== '') {
                    return $value;
                }
            }
            return '';
        };

        $munIbge = $v(["//*[local-name()='emit']//*[local-name()='cMun']", "//*[local-name()='CodigoMunicipio']"]);
        $munInfo = $this->resolveMunicipioByIbge($munIbge);

        $d = [
            'chave' => $v(["//*[local-name()='infNFSe']/@Id", "//*[local-name()='Nfse']/@Id"]),
            'numero_nfse' => $v(["//*[local-name()='nNFSe']", "//*[local-name()='Numero']"]),
            'competencia' => $this->formatDate($v(["//*[local-name()='DataCompetencia']", "//*[local-name()='Competencia']"])),
            'data_emissao_nfse' => $this->formatDateTime($v(["//*[local-name()='dhProc']", "//*[local-name()='dhEmi']", "//*[local-name()='DataEmissao']"])),
            'numero_rps' => $v(["//*[local-name()='IdentificacaoRps']/*[local-name()='Numero']"]),
            'serie_rps' => $v(["//*[local-name()='IdentificacaoRps']/*[local-name()='Serie']"]),
            'data_emissao_dps' => $this->formatDateTime($v(["//*[local-name()='DhEmi']", "//*[local-name()='DataEmissao']"])),
            'qrcode_payload' => '',
            'ambiente_gerador_desc' => $this->mapByCode($v(["//*[local-name()='ambGer']"]), NfseCodigoPadrao::ambiente()),
            'ambiente_desc' => $this->mapByCode($v(["//*[local-name()='TpAmb']"]), NfseCodigoPadrao::ambiente()),
            'municipio_label' => $munInfo['nome'] !== '' ? ($munInfo['nome'] . ' - ' . $munInfo['uf']) : $munIbge,

            'situacao_nfse_desc' => $this->mapByCode($v(["//*[local-name()='cStat']", "//*[local-name()='Situacao']"]), NfseCodigoPadrao::situacaoNfse()),
            'finalidade_desc' => $this->mapByCode($v(["//*[local-name()='finNfse']", "//*[local-name()='Finalidade']"]), NfseCodigoPadrao::finalidadeNfse()),

            'emit_nome' => $v(["//*[local-name()='emit']/*[local-name()='xNome']", "//*[local-name()='PrestadorServico']//*[local-name()='RazaoSocial']"]),
            'emit_doc' => $v([
                "//*[local-name()='emit']/*[local-name()='CNPJ']",
                "//*[local-name()='PrestadorServico']/*[local-name()='IdentificacaoPrestador']/*[local-name()='Cnpj']",
                "//*[local-name()='PrestadorServico']//*[local-name()='Cnpj']"
            ]),
            'emit_im' => $v([
                "//*[local-name()='emit']/*[local-name()='IM']",
                "//*[local-name()='PrestadorServico']/*[local-name()='IdentificacaoPrestador']/*[local-name()='InscricaoMunicipal']",
                "//*[local-name()='PrestadorServico']//*[local-name()='InscricaoMunicipal']"
            ]),
            'emit_tel' => $v([
                "//*[local-name()='emit']//*[local-name()='Telefone']",
                "//*[local-name()='PrestadorServico']/*[local-name()='Contato']/*[local-name()='Telefone']"
            ]),
            'emit_email' => $v([
                "//*[local-name()='emit']//*[local-name()='email']",
                "//*[local-name()='PrestadorServico']/*[local-name()='Contato']/*[local-name()='Email']",
                "//*[local-name()='PrestadorServico']//*[local-name()='Email']"
            ]),
            'emit_mun_uf' => trim(
                $v([
                    "//*[local-name()='emit']//*[local-name()='cMun']",
                    "//*[local-name()='PrestadorServico']/*[local-name()='Endereco']/*[local-name()='CodigoMunicipio']"
                ]) . ' / ' .
                $v([
                    "//*[local-name()='emit']//*[local-name()='UF']",
                    "//*[local-name()='PrestadorServico']/*[local-name()='Endereco']/*[local-name()='Uf']"
                ])
            ),
            'emit_ibge_cep' => trim(
                $v([
                    "//*[local-name()='emit']//*[local-name()='cMun']",
                    "//*[local-name()='PrestadorServico']/*[local-name()='Endereco']/*[local-name()='CodigoMunicipio']"
                ]) . ' / ' .
                $v([
                    "//*[local-name()='emit']//*[local-name()='CEP']",
                    "//*[local-name()='PrestadorServico']/*[local-name()='Endereco']/*[local-name()='Cep']"
                ])
            ),
            'emit_end' => $this->joinAddressParts([
                $v([
                    "//*[local-name()='emit']//*[local-name()='xLgr']",
                    "//*[local-name()='PrestadorServico']/*[local-name()='Endereco']/*[local-name()='Endereco']"
                ]),
                $v([
                    "//*[local-name()='emit']//*[local-name()='nro']",
                    "//*[local-name()='PrestadorServico']/*[local-name()='Endereco']/*[local-name()='Numero']"
                ]),
                $v([
                    "//*[local-name()='emit']//*[local-name()='xBairro']",
                    "//*[local-name()='PrestadorServico']/*[local-name()='Endereco']/*[local-name()='Bairro']"
                ]),
            ]),
            'sn_comp_desc' => $this->mapByCode($v(["//*[local-name()='OptanteSimplesNacional']", "//*[local-name()='OpSimpNac']"]), NfseCodigoPadrao::simplesNacionalCompetencia()),
            'reg_ap_sn_desc' => $this->mapByCode($v(["//*[local-name()='RegApTribSN']"]), NfseCodigoPadrao::regimeApuracaoSn()),

            'toma_nome' => $v(["//*[local-name()='Tomador']//*[local-name()='RazaoSocial']", "//*[local-name()='TomadorServico']//*[local-name()='RazaoSocial']"]),
            'toma_doc' => $v([
                "//*[local-name()='Tomador']//*[local-name()='Cpf']",
                "//*[local-name()='Tomador']//*[local-name()='Cnpj']",
                "//*[local-name()='TomadorServico']/*[local-name()='IdentificacaoTomador']/*[local-name()='CpfCnpj']/*[local-name()='Cpf']",
                "//*[local-name()='TomadorServico']/*[local-name()='IdentificacaoTomador']/*[local-name()='CpfCnpj']/*[local-name()='Cnpj']"
            ]),
            'toma_im' => $v([
                "//*[local-name()='Tomador']//*[local-name()='InscricaoMunicipal']",
                "//*[local-name()='TomadorServico']/*[local-name()='IdentificacaoTomador']/*[local-name()='InscricaoMunicipal']"
            ]),
            'toma_tel' => $v([
                "//*[local-name()='Tomador']//*[local-name()='Telefone']",
                "//*[local-name()='TomadorServico']/*[local-name()='Contato']/*[local-name()='Telefone']"
            ]),
            'toma_email' => $v([
                "//*[local-name()='Tomador']//*[local-name()='Email']",
                "//*[local-name()='TomadorServico']/*[local-name()='Contato']/*[local-name()='Email']"
            ]),
            'toma_mun_uf' => trim(
                $v([
                    "//*[local-name()='Tomador']//*[local-name()='CodigoMunicipio']",
                    "//*[local-name()='TomadorServico']/*[local-name()='Endereco']/*[local-name()='CodigoMunicipio']"
                ]) . ' / ' .
                $v([
                    "//*[local-name()='Tomador']//*[local-name()='Uf']",
                    "//*[local-name()='TomadorServico']/*[local-name()='Endereco']/*[local-name()='Uf']"
                ])
            ),
            'toma_ibge_cep' => trim(
                $v([
                    "//*[local-name()='Tomador']//*[local-name()='CodigoMunicipio']",
                    "//*[local-name()='TomadorServico']/*[local-name()='Endereco']/*[local-name()='CodigoMunicipio']"
                ]) . ' / ' .
                $v([
                    "//*[local-name()='Tomador']//*[local-name()='Cep']",
                    "//*[local-name()='TomadorServico']/*[local-name()='Endereco']/*[local-name()='Cep']"
                ])
            ),
            'toma_end' => $this->joinAddressParts([
                $v([
                    "//*[local-name()='Tomador']//*[local-name()='Endereco']/*[local-name()='Endereco']",
                    "//*[local-name()='TomadorServico']/*[local-name()='Endereco']/*[local-name()='Endereco']"
                ]),
                $v([
                    "//*[local-name()='Tomador']//*[local-name()='Endereco']/*[local-name()='Numero']",
                    "//*[local-name()='TomadorServico']/*[local-name()='Endereco']/*[local-name()='Numero']"
                ]),
                $v([
                    "//*[local-name()='Tomador']//*[local-name()='Endereco']/*[local-name()='Bairro']",
                    "//*[local-name()='TomadorServico']/*[local-name()='Endereco']/*[local-name()='Bairro']"
                ]),
            ]),

            'trib_nac' => $this->resolveTribNac($v(["//*[local-name()='cTribNac']", "//*[local-name()='CTribNac']", "//*[local-name()='cTribNacional']", "//*[local-name()='CodigoTributacaoNacional']", "//*[local-name()='ItemListaServico']"])),
            'trib_mun' => $this->resolveTribMun($v(["//*[local-name()='xTribMun']", "//*[local-name()='cTribMun']", "//*[local-name()='CodigoTributacaoMunicipio']"])),
            'nbs' => $v(["//*[local-name()='CodigoNbs']"]),
            'local_prest' => $v(["//*[local-name()='xLocPrestacao']", "//*[local-name()='CodigoMunicipio']"]),
            'serv_desc' => $v(["//*[local-name()='xDescServ']", "//*[local-name()='Discriminacao']"]),
            'item_lista' => $v(["//*[local-name()='ItemListaServico']"]),
            'cnae' => $v(["//*[local-name()='CodigoCnae']", "//*[local-name()='cClassTrib']"]),
            'cod_trib_mun' => $v(["//*[local-name()='CodigoTributacaoMunicipio']"]),

            'reg_esp_issqn_desc' => $this->mapByCode($v(["//*[local-name()='RegimeEspecialTributacao']", "//*[local-name()='RegEspTrib']"]), NfseCodigoPadrao::regimeEspecialIssqn()),
            'trib_issqn_desc' => $this->mapByCode($v(["//*[local-name()='NaturezaOperacao']", "//*[local-name()='TribIssqn']"]), NfseCodigoPadrao::tipoTributacaoIssqn()),
            'mun_incid' => $v(["//*[local-name()='MunicipioIncidencia']", "//*[local-name()='cLocIncid']"]),
            'proc_susp' => $v(["//*[local-name()='NProcesso']"]),
            'benef_mun' => $v(["//*[local-name()='BeneficioMunicipal']//*[local-name()='TpBM']"]),
            'calc_bm' => $this->money($v(["//*[local-name()='BeneficioMunicipal']//*[local-name()='VRedBCBM']"])),
            'ded_red' => $this->money($v(["//*[local-name()='ValorDeducoes']"])),
            'desc_incond' => $this->money($v(["//*[local-name()='DescontoIncondicionado']"])),
            'bc_issqn' => $this->money($v(["//*[local-name()='BaseCalculo']", "//*[local-name()='vBC']"])),
            'aliq' => $this->percent($v(["//*[local-name()='Aliquota']", "//*[local-name()='pAliqAplic']"])),
            'ret_issqn_desc' => $this->mapByCode($v(["//*[local-name()='IssRetido']", "//*[local-name()='TpRetIssqn']"]), NfseCodigoPadrao::retencaoIssqn()),
            'v_issqn' => $this->money($v(["//*[local-name()='ValorIss']", "//*[local-name()='vISSQN']"])),
            'v_irrf' => $this->money($v(["//*[local-name()='ValorIr']"])),
            'v_prev' => $this->money($v(["//*[local-name()='ValorInss']"])),
            'v_soc' => $this->money($v(["//*[local-name()='ValorCsll']"])),
            'v_pis' => $this->money($v(["//*[local-name()='ValorPis']"])),
            'v_cofins' => $this->money($v(["//*[local-name()='ValorCofins']"])),

            'ibs_exclusoes' => $this->money($v(["//*[local-name()='VRedBC']"])),
            'ibs_base_apos' => $this->money($v(["//*[local-name()='VBCPosReducao']"])),
            'ibs_red_aliq' => $this->fitPair($this->percent($v(["//*[local-name()='pRedAliqIBS']"])), $this->percent($v(["//*[local-name()='pRedAliqCBS']"]))),
            'ibs_aliq_uf_mun' => $this->fitPair($this->percent($v(["//*[local-name()='pAliqIBSUF']"])), $this->percent($v(["//*[local-name()='pAliqIBSMun']"]))),
            'ibs_aliq_mun' => $this->percent($v(["//*[local-name()='pAliqEfetMun']"])),
            'ibs_valor_mun' => $this->money($v(["//*[local-name()='vIBSMun']"])),
            'ibs_aliq_est' => $this->percent($v(["//*[local-name()='pAliqEfetUF']"])),
            'ibs_valor_est' => $this->money($v(["//*[local-name()='vIBSUF']"])),

            'v_serv' => $this->money($v(["//*[local-name()='ValorServicos']", "//*[local-name()='vServ']"])),
            'desc_cond' => $this->money($v(["//*[local-name()='DescontoCondicionado']"])),
            'ret_total' => $this->money($v(["//*[local-name()='ValorIssRetido']"])),
            'v_liq' => $this->money($v(["//*[local-name()='ValorLiquidoNfse']", "//*[local-name()='vLiq']"])),
            'v_ibscbs' => $this->money($v(["//*[local-name()='IBSValorTotal']"])),
            'v_liq_ibscbs' => $this->money($v(["//*[local-name()='ValorTotalComTributos']"])),
            'info_comp' => $v(["//*[local-name()='InformacoesComplementares']", "//*[local-name()='xInfComp']"]),
            'tot_aprox_fed_v' => $this->money($v(["//*[local-name()='vTotTribFed']", "//*[local-name()='vTribFed']"])),
            'tot_aprox_est_v' => $this->money($v(["//*[local-name()='vTotTribEst']", "//*[local-name()='vTribEst']"])),
            'tot_aprox_mun_v' => $this->money($v(["//*[local-name()='vTotTribMun']", "//*[local-name()='vTribMun']"])),
            'tot_aprox_fed_p' => $this->percent($v(["//*[local-name()='pTotTribFed']", "//*[local-name()='pTribFed']"])),
            'tot_aprox_est_p' => $this->percent($v(["//*[local-name()='pTotTribEst']", "//*[local-name()='pTribEst']"])),
            'tot_aprox_mun_p' => $this->percent($v(["//*[local-name()='pTotTribMun']", "//*[local-name()='pTribMun']"])),
        ];

        $d['qrcode_payload'] = 'https://www.nfse.gov.br/ConsultaPublica/?tpc=1&chave=' . $d['chave'];
        $d['local_prest_desc'] = $this->resolveLocalPrestacao($d['local_prest']);
        if ($d['v_liq_ibscbs'] === '-') {
            $sum = $this->sumMoney($d['v_liq'], $d['v_ibscbs']);
            if ($sum !== null) {
                $d['v_liq_ibscbs'] = $this->money((string) $sum);
            }
        }
        $d['info_comp'] = $this->appendTotaisAproximadosTributos($d);

        return $d;
    }

    private function appendTotaisAproximadosTributos(array $d): string
    {
        $info = trim((string) ($d['info_comp'] ?? ''));
        $marker = 'Totais aproximados dos tributos';
        if (stripos($info, $marker) !== false) {
            return $info;
        }

        $fed = $this->preferTaxValue((string) ($d['tot_aprox_fed_v'] ?? '-'), (string) ($d['tot_aprox_fed_p'] ?? '-'));
        $est = $this->preferTaxValue((string) ($d['tot_aprox_est_v'] ?? '-'), (string) ($d['tot_aprox_est_p'] ?? '-'));
        $mun = $this->preferTaxValue((string) ($d['tot_aprox_mun_v'] ?? '-'), (string) ($d['tot_aprox_mun_p'] ?? '-'));

        if ($fed === '-' && $est === '-' && $mun === '-') {
            return $info;
        }

        $line = 'Totais aproximados dos tributos cfe. Lei nº 12.741/2012: '
            . 'Federais: ' . $fed . '; '
            . 'Estaduais: ' . $est . '; '
            . 'Municipais: ' . $mun . ';';

        if ($info === '') {
            return $line;
        }
        return $info . "\n" . $line;
    }

    private function preferTaxValue(string $valueMoney, string $valuePercent): string
    {
        if ($valueMoney !== '-') {
            return $valueMoney;
        }
        if ($valuePercent !== '-') {
            return $valuePercent;
        }
        return '-';
    }

    private function resolveLocalPrestacao(string $local): string
    {
        $local = trim($local);
        if ($local === '') {
            return '-';
        }
        if (preg_match('/^\d{7}$/', $local) === 1) {
            $info = $this->resolveMunicipioByIbge($local);
            if ($info['nome'] !== '') {
                return $local . ' - ' . $info['nome'] . ' / ' . $info['uf'] . ' / Brasil';
            }
        }
        return $local;
    }

    private function resolveTribNac(string $value): string
    {
        $raw = trim($value);
        if ($raw === '') {
            return '-';
        }

        $digits = preg_replace('/\D+/', '', $raw);
        if (!is_string($digits) || $digits === '') {
            return $raw;
        }

        $candidates = [$digits];
        if (strlen($digits) < 6) {
            $candidates[] = str_pad($digits, 6, '0', STR_PAD_LEFT);
        }
        if (strlen($digits) === 4) {
            $candidates[] = $digits . '01';
        }

        $resolvedCode = $digits;
        $desc = '';
        foreach (array_unique($candidates) as $code) {
            $found = NfseCodigoPadrao::descricaoTributacaoNacional($code);
            if ($found !== '') {
                $resolvedCode = $code;
                $desc = $found;
                break;
            }
        }

        if ($desc === '') {
            return $raw;
        }
        return $resolvedCode . ' - ' . $desc;
    }

    private function resolveTribMun(string $value): string
    {
        $raw = trim($value);
        if ($raw === '') {
            return '-';
        }

        // Se já vier com descrição, não tenta remapear.
        if (strpos($raw, ' - ') !== false) {
            return $raw;
        }

        // Só tenta mapear quando for um código puro.
        if (preg_match('/^\d{4,15}$/', $raw) !== 1) {
            return $raw;
        }

        // Quando vier somente código municipal, tenta aproveitar o catálogo
        // nacional para exibir descrição quando houver correspondência.
        $digits = $raw;
        if (!is_string($digits) || $digits === '') {
            return $raw;
        }

        $candidates = [$digits];
        if (strlen($digits) >= 4) {
            $candidates[] = substr($digits, 0, 6);
            $candidates[] = substr($digits, 0, 4) . '01';
            $candidates[] = substr($digits, 0, 4);
        }

        foreach (array_unique($candidates) as $code) {
            if ($code === '') {
                continue;
            }
            $desc = NfseCodigoPadrao::descricaoTributacaoNacional($code);
            if ($desc !== '') {
                return $raw . ' - ' . $desc;
            }
        }

        return $raw;
    }

    private function resolveMunicipioByIbge(string $ibge): array
    {
        $ibgeInt = (int) preg_replace('/\D+/', '', $ibge);
        if ($ibgeInt <= 0) {
            return ['nome' => '', 'uf' => ''];
        }
        if (self::$municipiosCache === null) {
            $catalogPath = dirname(__DIR__, 2) . '/storage/municipios-catalog.php';
            $cache = [];
            if (is_file($catalogPath)) {
                $catalog = require $catalogPath;
                if (is_array($catalog) && isset($catalog['municipios']) && is_array($catalog['municipios'])) {
                    foreach ($catalog['municipios'] as $key => $item) {
                        if (is_array($item) && isset($item['nome'], $item['uf'])) {
                            $cache[(int) $key] = ['nome' => (string) $item['nome'], 'uf' => (string) $item['uf']];
                        }
                    }
                }
            }
            self::$municipiosCache = $cache;
        }
        return self::$municipiosCache[$ibgeInt] ?? ['nome' => '', 'uf' => ''];
    }

    private function joinAddressParts(array $parts): string
    {
        $clean = [];
        foreach ($parts as $part) {
            $value = trim((string) $part);
            if ($value === '') {
                continue;
            }

            if (preg_match('/^null\b/i', $value) === 1) {
                $value = trim(preg_replace('/^null\b\s*/i', '', $value) ?? '');
            }

            if ($value === '' || strcasecmp($value, 'null') === 0) {
                continue;
            }

            $clean[] = $value;
        }

        return trim(implode(' ', $clean));
    }

    private function formatDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        try {
            return (new DateTimeImmutable($value))->format('d/m/Y');
        } catch (\Throwable $e) {
            $ts = strtotime($value);
            return $ts === false ? $value : date('d/m/Y', $ts);
        }
    }

    private function formatDateTime(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        try {
            return (new DateTimeImmutable($value))->format('d/m/Y H:i:s');
        } catch (\Throwable $e) {
            $ts = strtotime($value);
            return $ts === false ? $value : date('d/m/Y H:i:s', $ts);
        }
    }

    private function mapByCode(string $code, array $map): string
    {
        $code = trim($code);
        if ($code === '') {
            return '-';
        }
        return isset($map[$code]) ? ($code . ' - ' . $map[$code]) : $code;
    }

    private function money(string $value): string
    {
        $num = $this->toFloat($value);
        if ($num === null) {
            return $this->safe($value);
        }
        return $this->txt('R$ ') . number_format($num, 2, ',', '.');
    }

    private function percent(string $value): string
    {
        $num = $this->toFloat($value);
        if ($num === null) {
            return $this->safe($value);
        }
        return number_format($num, 2, ',', '.') . $this->txt('%');
    }

    private function toFloat(string $value)
    {
        $value = trim(str_replace(['R$', '%', ' '], '', $value));
        if ($value === '') {
            return null;
        }
        if (strpos($value, ',') !== false && strpos($value, '.') !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (strpos($value, ',') !== false) {
            $value = str_replace(',', '.', $value);
        }
        return is_numeric($value) ? (float) $value : null;
    }

    private function sumMoney(string $a, string $b)
    {
        $na = $this->toFloat($a);
        $nb = $this->toFloat($b);
        if ($na === null || $nb === null) {
            return null;
        }
        return $na + $nb;
    }

    private function txt(string $value): string
    {
        $value = $this->normalizeUtf8Text((string) $value);

        if ($value === '') {
            return '';
        }

        $converted = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $value);

        return $converted !== false ? $converted : '';
    }

    private function normalizeUtf8Text(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
        }

        return strtr($value, [
            "\u{00A0}" => ' ',
            "\u{2007}" => ' ',
            "\u{202F}" => ' ',
            "\u{2010}" => '-',
            "\u{2011}" => '-',
            "\u{2012}" => '-',
            "\u{2013}" => '-',
            "\u{2014}" => '-',
            "\u{2018}" => "'",
            "\u{2019}" => "'",
            "\u{201C}" => '"',
            "\u{201D}" => '"',
            "\u{2026}" => '...',
            "\u{2212}" => '-',
        ]);
    }

    private function safe(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '-';
        }

        return $this->txt(mb_substr($value, 0, 320));
    }

    private function fit(FPDF $pdf, string $text, float $maxWidth): string
    {
        $text = trim($text);
        $text = $text === '' ? '-' : mb_substr($text, 0, 320);

        $converted = $this->txt($text);

        if ($pdf->GetStringWidth($converted) <= $maxWidth) {
            return $converted;
        }

        $ellipsisUtf8 = '...';
        $ellipsis = $this->txt($ellipsisUtf8);

        while ($text !== '') {
            $text = mb_substr($text, 0, -1);
            $converted = $this->txt(rtrim($text) . $ellipsisUtf8);

            if ($pdf->GetStringWidth($converted) <= $maxWidth) {
                return $converted;
            }
        }

        return $ellipsis;
    }

    private function fitPair(string $a, string $b): string
    {
        $a = trim($a);
        $b = trim($b);
        if ($a === '' && $b === '') {
            return '-';
        }
        if ($b === '' || $b === '-') {
            return $a;
        }
        if ($a === '' || $a === '-') {
            return $b;
        }
        return $a . ' / ' . $b;
    }
}

<?php
declare(strict_types=1);
namespace Alves\NfseBrasil\Danfse;

use RuntimeException;

/** DANFSe v2.0 — NT SE/CGNFS-e 008, com fontes e disposição do Anexo I. */
final class DanfsePdfGenerator
{
    private DanfseCanvas $pdf;
    private float $x = 3.0;
    private float $w = 204.0;

    public function generate(string $nfseXml, $provider = null, array $context = []): string
    {
        $d = $this->extractData($nfseXml);
        $d['layout_nacional'] = $d['nacional'] || ($context['layout'] ?? '') === 'nacional';
        if (isset($context['chave']) && $context['chave'] !== '' && $context['chave'] !== $d['chave']) {
            throw new RuntimeException('A situação fiscal informada não pertence ao XML da NFS-e.');
        }
        $pdf = $this->pdf = new DanfseCanvas('P', 'mm', 'A4');
        $pdf->SetMargins(3, 3, 3);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetTitle('DANFSe ' . $d['numero_nfse'], true);
        $pdf->SetCreator('nfse-php-brazil - DANFSe v2.0', true);
        foreach ([['DanfseArial', '', 'arial.php'], ['DanfseArial', 'B', 'arialbd.php'], ['DanfseSans', '', 'micross.php']] as [$family, $style, $file]) {
            if (method_exists(\FPDF::class, '_loadjsonfont')) { $file = str_replace('.php', '.json', $file); }
            $path = __DIR__ . '/assets/fonts/' . $file;
            if (!is_file($path)) { throw new RuntimeException('Fonte obrigatória do DANFSe não instalada: ' . $file); }
            $pdf->AddFont($family, $style, $file);
        }
        $pdf->AddPage();
        $pdf->SetLineWidth(0.5 * 25.4 / 72);
        $this->header($d);
        $nbs = preg_replace('/\D+/', '', $d['nbs']);
        if (strlen($nbs) === 9) { $d['nbs'] = preg_replace('/^(\d)(\d{4})(\d{2})(\d{2})$/', '$1.$2.$3.$4', $nbs); }
        if (preg_match('/^\d{6}$/', $d['trib_nac'])) { $d['trib_nac'] = preg_replace('/^(\d{2})(\d{2})(\d{2})$/', '$1.$2.$3', $d['trib_nac']); }
        $y = 43.4;
        $recovered = 0;
        foreach ([['prestador', 'PRESTADOR / FORNECEDOR'], ['tomador', 'TOMADOR / ADQUIRENTE'], ['destinatario', 'DESTINATÁRIO DA OPERAÇÃO'], ['intermediario', 'INTERMEDIÁRIO DA OPERAÇÃO']] as [$key, $title]) {
            $p = $d[$key];
            if ($key !== 'prestador' && ($p['nome'] === '' && $p['doc'] === '' || $key === 'destinatario' && $d['ind_dest'] === '0')) {
                $message = $key === 'destinatario' && $d['ind_dest'] === '0' && $d['tomador']['nome'] !== ''
                    ? 'O DESTINATÁRIO É O PRÓPRIO TOMADOR/ADQUIRENTE DA OPERAÇÃO'
                    : ['tomador' => 'TOMADOR/ADQUIRENTE DA OPERAÇÃO NÃO IDENTIFICADO NA NFS-e', 'destinatario' => 'DESTINATÁRIO DA OPERAÇÃO NÃO IDENTIFICADO NA NFS-e', 'intermediario' => 'INTERMEDIÁRIO DA OPERAÇÃO NÃO IDENTIFICADO NA NFS-e'][$key];
                $this->separator($y);
                $this->text($this->x + 1, $y + 1.5, $this->w - 2, $message, true, 7);
                $y += 6.4; $recovered += 12.8;
                continue;
            }
            $y += $this->row($y, [[$title, '', 1, true], ['CNPJ / CPF / NIF', $p['doc']], ['Indicador Municipal (Inscrição)', $key === 'destinatario' ? '' : $p['im']], ['Telefone', $p['fone']]], true);
            $y += $this->row($y, [['Nome / Nome Empresarial', $this->limit($p['nome'], 80), 2], ['Município / Sigla UF', $p['municipio']], ['Código IBGE / CEP', $p['ibge_cep']]]);
            $y += $this->row($y, [['Endereço', $this->limit($p['endereco'], 80), 2], ['E-mail', $this->limit($p['email'], 80), 2]]);
            if ($key === 'prestador') {
                $y += $this->row($y, [['Simples Nacional na Data de Competência', $d['sn_comp_desc'], 2], ['Regime de Apuração Tributária pelo SN', $this->limit($d['reg_ap_sn_desc'], 80), 2]]);
            }
        }
        $y += $this->row($y, [['SERVIÇO PRESTADO', '', 1, true], ['Código de Tributação Nacional / Municipal', trim($d['trib_nac'] . ' / ' . $d['trib_mun'], ' /')], ['Código da NBS', $d['nbs']], ['Local da Prestação / Sigla UF / País', $d['local_prest_desc']]], true);
        $this->text($this->x + 1, $y, $this->w - 2, $this->limit($d['trib_desc'], 170), false, 7);
        $y += 3.8;
        $descricao = $this->limit($d['serv_desc'], 1300);
        $descriptionHeight = max(6.4 + $recovered, 3 + $this->lines($descricao, $this->w - 2) * 2.7);
        $this->field($this->x, $y, $this->w, 'Descrição do Serviço', $descricao, $descriptionHeight);
        $y += $descriptionHeight;
        if (!$d['incide_iss']) {
            $this->separator($y);
            $this->text($this->x + 1, $y + 1.5, $this->w - 2, 'TRIBUTAÇÃO MUNICIPAL (ISSQN) - OPERAÇÃO NÃO SUJEITA AO ISSQN', true, 7);
            $y += 6.4;
        } else {
            $y += $this->row($y, [['TRIBUTAÇÃO MUNICIPAL (ISSQN)', '', 1, true], ['Tipo de Tributação do ISSQN', $d['trib_issqn_desc']], ['Município / Sigla UF / País de Incidência do ISSQN', $d['mun_incid'], 2]], true);
            $y += $this->row($y, [['Regime Especial de Tributação do ISSQN', $d['reg_esp_issqn_desc']], ['Tipo de Imunidade do ISSQN', $this->limit($d['imunidade_desc'], 40)], ['Suspensão da Exigibilidade do ISSQN', $this->limit($d['suspensao_desc'], 40)], ['Número Processo Suspensão', $d['proc_susp']]]);
            $y += $this->row($y, [['Benefício Municipal', $d['benef_mun']], ['Cálculo do BM', $d['calc_bm']], ['Total Deduções/Reduções', $d['ded_red']], ['Desconto Incondicionado', $d['desc_incond']]]);
            $y += $this->row($y, [['BC ISSQN', $d['bc_issqn']], ['Alíquota Aplicada', $d['aliq']], ['Retenção do ISSQN', $d['ret_issqn_desc']], ['ISSQN Apurado', $d['v_issqn']]]);
        }
        $y += $this->row($y, [['TRIBUTAÇÃO FEDERAL (EXCETO CBS)', '', 1, true], ['IRRF', $d['v_irrf']], ['Contribuição Previdenciária - Retida', $d['v_prev']], ['Contribuições Sociais - Retidas', $d['v_soc']]], true);
        if ($d['ano'] <= 2026) {
            $y += $this->row($y, [['PIS - Débito Apuração Própria', $d['v_pis']], ['COFINS - Débito Apuração Própria', $d['v_cofins']], ['Descrição Contrib. Sociais - Retidas', $d['ret_fed_desc'], 2]]);
        }
        $y += $this->row($y, [['TRIBUTAÇÃO IBS / CBS', '', 1, true], ['CST / cClassTrib', $d['cst_class']], ['Indicador de Operação / Código IBGE Incidência / Município / Sigla UF', $d['ibs_local'], 2]], true);
        $y += $this->row($y, [['Exclusões e Reduções da Base de Cálculo', $d['ibs_exclusoes']], ['Base de Cálculo Após Exclusões e Reduções', $d['ibs_base_apos']], ['Red. Alíquotas IBS / Red. Alíquota CBS', $d['ibs_red_aliq']], ['Alíquota IBS UF / IBS Mun', $d['ibs_aliq_uf_mun']]]);
        $y += $this->row($y, [['Alíquota Efetiva Municipal - IBS', $d['ibs_aliq_mun']], ['Valor Apurado Municipal - IBS', $d['ibs_valor_mun']], ['Alíquota Efetiva Estadual - IBS', $d['ibs_aliq_est']], ['Valor Apurado Estadual - IBS', $d['ibs_valor_est']]]);
        $y += $this->row($y, [['Valor Total Apurado - IBS', $d['ibs_total']], ['Alíquota - CBS', $d['cbs_aliquota']], ['Alíquota Efetiva - CBS', $d['cbs_aliquota_efetiva']], ['Valor Total Apurado - CBS', $d['cbs_valor']]]);
        $y += $this->row($y, [['VALOR TOTAL DA NFS-e', '', 1, true], ['VALOR DA OPERAÇÃO / SERVIÇO', $d['v_serv']], ['Desconto Incondicionado', $d['desc_incond']], ['Desconto Condicionado', $d['desc_cond']]], true);
        $y += $this->row($y, [['Total das Retenções (ISSQN / Federais)', $d['ret_total']], ['VALOR LÍQUIDO DA NFS-e', $d['v_liq']], ['Total do IBS/CBS', $d['v_ibscbs']], ['VALOR LÍQUIDO DA NFS-e + IBS/CBS', $d['v_liq_ibscbs'], 1, true]], false, 6.7);
        $this->separator($y);
        $this->text($this->x + 1, $y + 0.3, $this->w - 2, 'INFORMAÇÕES COMPLEMENTARES', true, 7); $y += 4;
        $canhotoY = 287.0;
        $info = $this->limit($d['info_comp'], 2000);
        $infoHeight = $canhotoY - $y - 5.8;
        $this->paragraph($this->x + 1, $y, $this->w - 2, $infoHeight, $info);
        $this->paragraph($this->x + 1, $canhotoY - 5.5, $this->w - 2, 5.3, $d['totais_aproximados']);
        $this->separator($canhotoY);
        $this->row($canhotoY, [['DATA CIENTIFICAÇÃO:', '', 1], ['IDENTIFICAÇÃO E ASSINATURA', '', 1], ['Nº NFS-e / CHAVE NFS-e', $d['numero_nfse'] . ' / ' . $d['chave'], 2]], false, 6.7);
        $pdf->Line(54, $canhotoY, 54, $canhotoY + 6.7); $pdf->Line(105, $canhotoY, 105, $canhotoY + 6.7);
        $pdf->SetLineWidth(25.4 / 72);
        $pdf->Rect(1.75, 1.75, 206.5, 293.5);
        $marca = $d['marca_agua'];
        if (isset($context['situacao'])) {
            $marca = ['cancelada' => 'CANCELADA', 'substituida' => 'SUBSTITUÍDA'][$context['situacao']] ?? $marca;
        }
        if ($marca !== '') { $pdf->watermark($marca); }
        return $pdf->Output('S');
    }

    private function header(array $d): void
    {
        $p = $this->pdf;
        $p->SetFillColor(242, 242, 242); $p->Rect(3, 3, 204, 11.6, 'F');
        $p->Image(__DIR__ . '/assets/img/logo-nfs-e-horizontal.png', 4.9, 4.4, 40, 8.5);
        $this->text(54.1, 3.2, 101.9, $d['layout_nacional'] ? 'DANFSe v2.0' : 'DANFSe Municipal', true, 9, 'C');
        $this->text(54.1, 6.5, 101.9, 'Documento Auxiliar da NFS-e', true, 9, 'C');
        if ($d['tp_amb'] === '2') {
            $p->SetTextColor(255, 0, 0);
            $this->text(54.1, 10, 101.9, 'NFS-e SEM VALIDADE JURÍDICA', true, 9, 'C');
            $p->SetTextColor(0, 0, 0);
        }
        $municipio = substr(preg_replace('/\D/', '', $d['trib_nac']), 0, 2) === '99' ? '' : 'Município: ' . $d['municipio_label'];
        $this->text(156.2, 3.2, 50.8, $municipio, false, 8);
        $this->text(156.2, 9.5, 50.8, 'Ambiente Gerador: ' . $d['ambiente_gerador_desc'], false, 6);
        $this->text(156.2, 11.8, 50.8, 'Tipo de Ambiente: ' . $d['ambiente_desc'], false, 6);
        $this->separator(14.8);
        $this->field(3, 14.8, 153, preg_match('/^\d{50}$/', $d['chave']) ? 'CHAVE DE ACESSO DA NFS-e' : 'CHAVE / CÓDIGO DE VERIFICAÇÃO', $d['chave'], 7.7, true);
        $this->field(3, 22.7, 51, 'NÚMERO DA NFS-e', $d['numero_nfse'], 6.7, true);
        $this->field(54.1, 22.7, 51, 'COMPETÊNCIA DA NFS-e', $d['competencia'], 6.7, true);
        $this->field(105.1, 22.7, 51, 'DATA E HORA DA EMISSÃO DA NFS-e', $d['data_emissao_nfse'], 6.7, true);
        $this->field(3, 29.6, 51, 'NÚMERO DA DPS', $d['numero_rps'], 6.7, true);
        $this->field(54.1, 29.6, 51, 'SÉRIE DA DPS', $d['serie_rps'], 6.7, true);
        $this->field(105.1, 29.6, 51, 'DATA E HORA DA EMISSÃO DA DPS', $d['data_emissao_dps'], 6.7, true);
        $p->Rect(3, 36.5, 51, 6.7, 'F');
        $this->field(3, 36.5, 51, 'EMITENTE DA NFS-e', $d['emitente_desc'], 6.7, true);
        $this->field(54.1, 36.5, 51, 'SITUAÇÃO DA NFS-e', $this->limit($d['situacao_nfse_desc'], 40), 6.7, true);
        $this->field(105.1, 36.5, 51, 'FINALIDADE', $this->limit($d['finalidade_desc'], 40), 6.7, true);
        if ($d['qrcode_payload'] !== '') {
            $this->qr($d['qrcode_payload']);
            $p->SetFont('DanfseSans', '', 6);
            foreach (['A autenticidade desta NFS-e pode ser verificada pela', 'leitura deste código QR ou pela consulta da chave de', 'acesso no portal nacional da NFS-e'] as $i => $linha) {
                $p->Text(156.2, 35.6 + $i * 2.25, $this->txt($linha));
            }
        }
    }

    private function field(float $x, float $y, float $w, string $label, string $value, float $height = 6.4, bool $identificacao = false): void
    {
        $this->text($x + 1, $y + 0.2, $w - 2, $label, true, $identificacao ? 7 : 6);
        $this->paragraph($x + 1, $y + 2.7, $w - 2, $height - 2.7, $value);
    }
    private function row(float $y, array $fields, bool $separator = false, float $height = 6.4): float
    {
        foreach ($fields as $f) {
            if ($f[1] !== '') { $height = max($height, 2.7 + $this->lines($f[1], ($f[2] ?? 1) * 51 - 2) * 2.7); }
        }
        if ($separator) { $this->separator($y); }
        $x = $this->x;
        foreach ($fields as $f) {
            [$label, $value] = $f; $w = ($f[2] ?? 1) * 51;
            if ($f[3] ?? false) {
                $this->pdf->SetFillColor(242, 242, 242); $this->pdf->Rect($x, $y, $w, $height, 'F');
                if ($value === '') { $this->text($x + 1, $y + 0.3, $w - 2, $label, true, 7); }
                else { $this->field($x, $y, $w, $label, $value, $height); }
            } else { $this->field($x, $y, $w, $label, $value, $height); }
            $x += $w;
        }
        return $height;
    }
    private function separator(float $y): void { $this->pdf->Line($this->x, $y, $this->x + $this->w, $y); }
    private function text(float $x, float $y, float $w, string $value, bool $bold = false, float $size = 7, string $align = 'L'): void
    {
        $this->pdf->SetFont($bold ? 'DanfseArial' : 'DanfseSans', $bold ? 'B' : '', $size);
        $this->pdf->SetXY($x, $y); $this->pdf->Cell($w, 2.8, $this->txt($value === '' ? '-' : $value), 0, 0, $align);
    }
    private function paragraph(float $x, float $y, float $w, float $h, string $text): void
    {
        $this->pdf->SetFont('DanfseSans', '', 7);
        $text = $text === '' ? '-' : preg_replace('/\s+/u', ' ', $text);
        $lines = $this->lines($text, $w);
        if ($lines * 2.7 > $h + 0.3 || $h < 0) {
            throw new RuntimeException('O conteúdo não cabe no campo do DANFSe com a fonte mínima exigida: ' . mb_substr($text, 0, 55));
        }
        $this->pdf->SetXY($x, $y); $this->pdf->MultiCell($w, 2.7, $this->txt($text), 0, 'L');
    }
    private function lines(string $text, float $width): int
    {
        $this->pdf->SetFont('DanfseSans', '', 7);
        $text = $this->txt(preg_replace('/\s+/u', ' ', trim($text)));
        $line = ''; $count = 1;
        foreach (explode(' ', $text) as $word) {
            $candidate = trim($line . ' ' . $word);
            if ($this->pdf->GetStringWidth($candidate) <= $width - 2) { $line = $candidate; continue; }
            if ($line !== '') { $count++; $line = ''; }
            foreach (str_split($word) as $char) {
                if ($this->pdf->GetStringWidth($line . $char) > $width - 2) { $count++; $line = ''; }
                $line .= $char;
            }
        }
        return $count;
    }
    private function limit(string $text, int $max): string { return mb_strlen($text) > $max - 3 ? mb_substr($text, 0, $max - 3) . '...' : $text; }
    private function txt(string $text): string { return iconv('UTF-8', 'Windows-1252//TRANSLIT', $text); }
    private function qr(string $url): void
    {
        if (!class_exists('TCPDF2DBarcode') && class_exists('TCPDF')) {
            $tcpdf = new \ReflectionClass(\TCPDF::class);
            $legacy = dirname($tcpdf->getFileName()) . '/tcpdf_barcodes_2d.php';
            if (is_file($legacy)) { require_once $legacy; }
        }
        if (class_exists('TCPDF2DBarcode')) {
            $array = (new \TCPDF2DBarcode($url, 'QRCODE,M'))->getBarcodeArray();
        } elseif (class_exists(\Com\Tecnick\Barcode\Barcode::class)) {
            // TCPDF 8 delega o QR à dependência tc-lib-barcode.
            $obj = (new \Com\Tecnick\Barcode\Barcode())->getBarcodeObj('QRCODE,M', $url);
            $raw = $obj->getArray();
            $array = ['num_cols' => $raw['ncols'], 'num_rows' => $raw['nrows'],
                'bcode' => array_map('str_split', explode("\n", trim($obj->getGrid())))];
        } else {
            throw new RuntimeException('Gerador de QR Code obrigatório não está disponível.');
        }
        $quiet = 4; $nx = $array['num_cols'] + $quiet * 2; $ny = $array['num_rows'] + $quiet * 2;
        $cell = 15.2 / max($nx, $ny);
        $this->pdf->SetFillColor(255, 255, 255); $this->pdf->Rect(174.8, 16.7, 15.2, 15.2, 'F');
        $this->pdf->SetFillColor(0, 0, 0);
        foreach ($array['bcode'] as $r => $row) {
            foreach ($row as $c => $bit) {
                if ($bit) { $this->pdf->Rect(174.8 + ($c + $quiet) * $cell, 16.7 + ($r + $quiet) * $cell, $cell + 0.005, $cell + 0.005, 'F'); }
            }
        }
    }
    private function extractData(string $xml): array
    {
        if (preg_match('/<(?:\w+:)?infNFSe\b/', $xml) && str_contains($xml, 'http://www.sped.fazenda.gov.br/nfse')) {
            return (new NationalDanfseParser())->parse($xml);
        }
        return (new MunicipalDanfseParser())->parse($xml);
    }
}

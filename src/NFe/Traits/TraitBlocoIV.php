<?php

namespace NFePHP\DA\NFe\Traits;

/**
 * Bloco totais da NFCe
 */
trait TraitBlocoIV
{
    protected function blocoIV($y)
    {
        //$this->bloco4H = 13;

        //$aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => $this->fontStyle];
        //$this->pdf->textBox($this->margem, $y, $this->wPrint, $this->bloco4H, '', $aFont, 'T', 'C', true, '', false);

        $qtd = $this->det->length;
        $valor = $this->getTagValue($this->ICMSTot, 'vNF');
        $desconto = $this->getTagValue($this->ICMSTot, 'vDesc');
        $frete = $this->getTagValue($this->ICMSTot, 'vFrete');
        $bruto = $valor + $desconto - $frete;

        $aFont = ['font' => $this->fontePadrao, 'size' => 8, 'style' => $this->fontStyle];
        $texto = "Qtde total de itens";
        $this->pdf->textBox(
            $this->margem,
            $y,
            $this->wPrint / 2,
            3,
            $texto,
            $aFont,
            'T',
            'L',
            false,
            '',
            false
        );
        $y1 = $this->pdf->textBox(
            $this->margem + $this->wPrint / 2,
            $y,
            $this->wPrint / 2,
            3,
            $qtd,
            $aFont,
            'T',
            'R',
            false,
            '',
            false
        );

        if ($valor != $bruto) {
            $texto = "Valor Total R$";
            $this->pdf->textBox(
                $this->margem,
                $y + $y1,
                $this->wPrint / 2,
                3,
                $texto,
                $aFont,
                'T',
                'L',
                false,
                '',
                false
            );
            $texto = number_format((float) $bruto, 2, ',', '.');
            $y2 = $this->pdf->textBox(
                $this->margem + $this->wPrint / 2,
                $y + $y1,
                $this->wPrint / 2,
                3,
                $texto,
                $aFont,
                'T',
                'R',
                false,
                '',
                false
            );
        }

        if ($desconto > 0) {
            $texto = "Desconto R$";
            $this->pdf->textBox(
                $this->margem,
                $y + $y1 + ($y2 ?? 0),
                $this->wPrint / 2,
                3,
                $texto,
                $aFont,
                'T',
                'L',
                false,
                '',
                false
            );
            $texto = number_format((float) $desconto, 2, ',', '.');
            $y3 = $this->pdf->textBox(
                $this->margem + $this->wPrint / 2,
                $y + $y1 + ($y2 ?? 0),
                $this->wPrint / 2,
                3,
                $texto,
                $aFont,
                'T',
                'R',
                false,
                '',
                false
            );
        }

        if ($frete > 0) {
            $texto = "Frete R$";
            $this->pdf->textBox(
                $this->margem,
                $y + $y1 + ($y2 ?? 0) + ($y3 ?? 0),
                $this->wPrint / 2,
                3,
                $texto,
                $aFont,
                'T',
                'L',
                false,
                '',
                false
            );
            $texto = number_format((float) $frete, 2, ',', '.');
            $y4 = $this->pdf->textBox(
                $this->margem + $this->wPrint / 2,
                $y + $y1 + ($y2 ?? 0) + ($y3 ?? 0),
                $this->wPrint / 2,
                3,
                $texto,
                $aFont,
                'T',
                'R',
                false,
                '',
                false
            );
        }

        $fsize = 10;
        if ($this->paperwidth < 70) {
            $fsize = 8;
        }
        $aFont = ['font' => $this->fontePadrao, 'size' => $fsize, 'style' => 'B'];
        $texto = "Valor a Pagar R$";
        $this->pdf->textBox(
            $this->margem,
            $y + $y1 + ($y2 ?? 0) + ($y3 ?? 0) + ($y4 ?? 0),
            $this->wPrint / 2,
            3,
            $texto,
            $aFont,
            'T',
            'L',
            false,
            '',
            false
        );
        $texto = number_format((float) $valor, 2, ',', '.');
        $y4 = $this->pdf->textBox(
            $this->margem + $this->wPrint / 2,
            $y + $y1 + ($y2 ?? 0) + ($y3 ?? 0) + ($y4 ?? 0),
            $this->wPrint / 2,
            3,
            $texto,
            $aFont,
            'T',
            'R',
            false,
            '',
            false
        );

        $this->pdf->dashedHLine($this->margem, $this->bloco4H + $y, $this->wPrint, 0.1, 30);
        return $this->bloco4H + $y;
    }
}

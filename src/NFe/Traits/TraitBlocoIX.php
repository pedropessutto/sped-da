<?php

namespace NFePHP\DA\NFe\Traits;

/**
 * Bloco Informações sobre impostos aproximados
 */
trait TraitBlocoIX
{
    protected function blocoIX($y)
    {
        $aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => $this->fontStyle];
        $valor = $this->getTagValue($this->ICMSTot, 'vTotTrib');
        $trib = !empty($valor) ? number_format((float) $valor, 2, ',', '.') : '-----';
        $texto = "Tributos totais Incidentes (Lei Federal 12.741/2012): R$ {$trib}";
        $aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => $this->fontStyle];
        $this->pdf->textBox(
            $this->margem,
            $y,
            $this->wPrint,
            $this->bloco9H,
            $texto,
            $aFont,
            'T',
            'C',
            false,
            '',
            true
        );

        if ($this->paperwidth < 70) {
            $fsize = 5;
            $aFont = ['font'=> $this->fontePadrao, 'size' => 5, 'style' => 'B'];
        } else {
			$aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => 'B'];
		}

        $this->pdf->textBox(
            $this->margem,
            $y+5,
            $this->wPrint,
            $this->bloco9H-4,
            str_replace(";", "\n", $this->infCpl),
            $aFont,
            'T',
            'C',
            null,
            '',
            false
        );
        return $this->bloco9H + $y;
    }

    /**
     * Calcula a altura do bloco IX
     * Depende do conteudo de infCpl
     *
     * @return int
     */
    protected function calculateHeighBlokIX()
    {
        $papel = [$this->paperwidth, 100];
        $wprint = $this->paperwidth - (2 * $this->margem);
        $logoAlign = 'L';
        $orientacao = 'P';
        $pdf = new \NFePHP\DA\Legacy\Pdf($orientacao, 'mm', $papel);
        $fsize = 7;
        $aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => $this->fontStyle];
        if ($this->paperwidth < 70) {
            $fsize = 5;
            $aFont = ['font'=> $this->fontePadrao, 'size' => 5, 'style' => $this->fontStyle];
        }
        $linhas = str_replace(';', "\n", $this->infCpl);
        $hfont = (imagefontheight($fsize)/72)*13;
        $numlinhas = $pdf->getNumLines($linhas, $wprint, $aFont)+2;
        return (int) ($numlinhas * $hfont) + 4;
    }
}

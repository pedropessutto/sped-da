<?php

namespace NFePHP\DA\NFe\Traits;

/**
 * Bloco forma de pagamento
 */
trait TraitBlocoV
{
	protected function blocoV($y)
	{
		$this->bloco5H = $this->calculateHeightPag();

		$aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => $this->fontStyle];
//$this->pdf->textBox($this->margem, $y, $this->wPrint, $this->bloco5H, '', $aFont, 'T', 'C', true, '', false);
		$arpgto = [];
		if ($this->pag->length > 0) {
			foreach ($this->pag as $pgto) {

				if ($this->card->length > 0){
					$cAut = $this->getTagValue($this->card[0], 'cAut');
					$tBand = $this->tBand($this->getTagValue($this->card[0], 'tBand')) ?? '';
					$tPag = $this->getTagValue($pgto, 'tPag') ?? '';
				}

				$tipo = $this->pagType((int) $this->getTagValue($pgto, 'tPag'));
				$valor = number_format((float) $this->getTagValue($pgto, 'vPag'), 2, ',', '.');
				$arpgto[] = [
					'tipo' => $tipo,
					'tPag' => $tPag ?? '',
					'valor' => $valor,
					'caut' => $cAut ?? '',
					'tband' => $tBand ?? ''
				];
			}
		} else {
			$tipo = $this->pagType((int) $this->getTagValue($this->pag, 'tPag'));
			$valor = number_format((float) $this->getTagValue($pgto, 'vPag'), 2, ',', '.');
			$arpgto[] = [
				'tipo' => $tipo,
				'valor' => $valor,
				'caut' => '',
				'tband' => ''
			];
		}
		$aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => $this->fontStyle];
		$texto = "FORMA PAGAMENTO";
		$this->pdf->textBox($this->margem, $y, $this->wPrint, 4, $texto, $aFont, 'T', 'L', false, '', false);
		$texto = "VALOR PAGO R$";
		$y1 = $this->pdf->textBox($this->margem, $y, $this->wPrint, 4, $texto, $aFont, 'T', 'R', false, '', false);

		$z = $y + $y1;
		foreach ($arpgto as $p) {
			if (in_array($p['tPag'], ['03','04','17','10','11','12'])) {
				$texto = $p['tipo']
					. (($p['tband'] != '' ? " - " . $p['tband'] : '')
						. ($p['caut'] != '' ? " - " . $p['caut'] : ''));
			}
			else
				$texto = $p['tipo'];

			$this->pdf->textBox($this->margem, $z, $this->wPrint, 3, $texto, $aFont, 'T', 'L', false, '', false);
			$y2 = $this->pdf->textBox(
				$this->margem,
				$z,
				$this->wPrint,
				3,
				$p['valor'],
				$aFont,
				'T',
				'R',
				false,
				'',
				false
			);
			$z += $y2;
		}

		/*if ($this->vTroco) {
			$texto = "Troco R$";
			$this->pdf->textBox($this->margem, $z, $this->wPrint, 3, $texto, $aFont, 'T', 'L', false, '', false);
			$texto =  !empty($this->vTroco) ? number_format((float) $this->vTroco, 2, ',', '.') : '0,00';
			$y1 = $this->pdf->textBox($this->margem, $z, $this->wPrint, 3, $texto, $aFont, 'T', 'R', false, '', false);
		}*/

		$this->pdf->dashedHLine($this->margem, $this->bloco5H+$y, $this->wPrint, 0.1, 30);
		return $this->bloco5H + $y;
	}

	protected function pagType($type)
	{
		$lista = [
			1 => 'Dinheiro',
			2 => 'Cheque',
			3 => 'Cartão de Crédito',
			4 => 'Cartão de Débito',
			5 => 'Crédito Loja',
			10 => 'Vale Alimentação',
			11 => 'Vale Refeição',
			12 => 'Vale Presente',
			13 => 'Vale Combustível',
			15 => 'Boleto Bancário',
			16 => 'Depósito Bancário',
			17 => 'Pagamento Instantâneo (PIX)',
			18 => 'Transferência bancária, Carteira Digital',
			19 => 'Programa de fidelidade, Cashback, Crédito Virtual',
			90 => 'Sem pagamento',
			99 => 'Outros',
		];
		return $lista[$type];
	}

	protected function tBand($type)
	{
		$lista = [
			'10' => 'Alelo',
			'03' => 'American Express',
			'08' => 'Aura',
			'11' => 'Banes Card',
			'09' => 'Cabal',
			'12' => 'CalCard',
			'13' => 'Credz',
			'05' => 'Diners Club',
			'14' => 'Discover',
			'06' => 'Elo',
			'15' => 'GoodCard',
			'16' => 'GreenCard',
			'17' => 'Hiper',
			'07' => 'Hipercard',
			'18' => 'JcB',
			'02' => 'Mastercard',
			'19' => 'Mais',
			'20' => 'MaxVan',
			'21' => 'Policard',
			'22' => 'RedeCompras',
			'23' => 'Sodexo',
			'04' => 'Sorocred',
			'24' => 'ValeCard',
			'25' => 'Verocheque',
			'01' => 'Visa',
			'26' => 'VR',
			'27' => 'Ticket',
			'99' => 'Outros'
		];
		return $lista[$type] ?? null;
	}

	protected function calculateHeightPag()
	{
		$n = $this->pag->length > 0 ? $this->pag->length : 1;
		$height = 4 + (2.4 * $n) + 3;
		return $height;
	}
}

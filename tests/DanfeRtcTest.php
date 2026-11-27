<?php

namespace NFePHP\DA\Tests;

use NFePHP\DA\NFe\Danfe;
use NFePHP\DA\NFe\DanfeRtc;
use PHPUnit\Framework\TestCase;
use Smalot\PdfParser\Parser;

class DanfeRtcTest extends TestCase
{
    public function testRenderDanfeRtc()
    {
        $xml = file_get_contents($this->fixture());
        $pdf = (new DanfeRtc($xml))->render();

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }

    public function testDanfeAtualContinuaRenderizando()
    {
        $xml = file_get_contents($this->fixture());
        $pdf = (new Danfe($xml))->render();

        $this->assertStringStartsWith('%PDF', $pdf);
    }

    /**
     * Simples Nacional: sem ICMS/IPI/IS no XML, o item mostra só IBS/CBS, com rótulo.
     */
    public function testItemSimplesMostraSoOQueExisteNoXml()
    {
        $danfe = $this->danfe('nfe_rtc_itens.xml');

        $this->assertSame([
            'bases' => [[['IBS/CBS', '10,00']]],
            'aliquotas' => [[['CBS', '0,90%'], ['IBS UF', '0,10%']], [['IBS MUN', '0,00%']]],
            'valores' => [[['CBS', '0,09'], ['IBS UF', '0,01']], [['IBS MUN', '0,00']]],
        ], $danfe->tributos(0));
    }

    /**
     * Com gRed a alíquota impressa é a efetiva (NT 2026.010, item 4.3).
     */
    public function testItemComReducaoMostraAliquotaEfetiva()
    {
        $danfe = $this->danfe('nfe_rtc_itens.xml');

        $this->assertSame(
            [[['CBS', '0,36%'], ['IBS UF', '0,04%']], [['IBS MUN', '0,00%']]],
            $danfe->tributos(4)['aliquotas']
        );
    }

    /**
     * pAliqEfet é 3v2-4: a alíquota efetiva sai com até 4 casas, sem arredondar.
     */
    public function testAliquotaEfetivaComQuatroCasas()
    {
        $danfe = $this->danfe('nfe_rtc_aliquota_quatro_casas.xml');

        $this->assertSame(
            [[['CBS', '0,3575%'], ['IBS UF', '0,0423%']], [['IBS MUN', '0,00%']]],
            $danfe->tributos(4)['aliquotas']
        );
    }

    /**
     * Com a coluna mais larga, o par do modelo cabe em 6 pt e permanece na mesma linha.
     */
    public function testParDoModeloCabeNaColuna()
    {
        $danfe = $this->danfe('nfe_rtc_completa.xml');

        $this->assertSame(
            [
                [['ICMS', '12,00%'], ['CBS', '0,36%']],
                [['IBS UF', '0,04%'], ['IBS MUN', '0,00%']],
            ],
            $danfe->tributos(1)['aliquotas']
        );
    }

    /**
     * O modelo da NT não tem a coluna do código, que o MOC não deixa suprimir: vai no início da descrição.
     */
    /**
     * ICMS continua na coluna mesmo sem valor: orig/CST (ou CSOSN) e, na linha de baixo, o CST do IBS.
     * Base, alíquota e valor do ICMS só entram quando a tag existe no XML.
     */
    public function testColunaCstMostraIcmsEIbs()
    {
        $danfe = $this->danfe('nfe_rtc_regime_normal.xml');

        $this->assertSame(['000', '5102'], $danfe->identificacao(0));
        $this->assertSame(['0102', '5102'], $danfe->identificacao(1));
        $this->assertSame([[['IBS/CBS', '25,50']]], $danfe->tributos(1)['bases']);
    }

    /**
     * Lote, ANVISA, FCI, veículo e quebra no ";". Quantidade e valor unitário usam 4 casas
     * só quando passam de 2; 2,5000 vira 2,50 e 1,2345 permanece 1,2345.
     */
    public function testDescricaoReaproveitaLoteAnvisaFciVeiculoECasas()
    {
        $xml = file_get_contents($this->fixture('nfe_rtc_minimo.xml'));
        $xml = preg_replace(
            '#<xProd>[^<]*</xProd>#',
            '<xProd>A&amp;ccedil;&amp;uacute;car; cristal</xProd>',
            $xml,
            1
        );
        $xml = preg_replace('#<qCom>[^<]+</qCom>#', '<qCom>2.5000</qCom>', $xml, 1);
        $xml = preg_replace('#<vUnCom>[^<]+</vUnCom>#', '<vUnCom>1.2345000000</vUnCom>', $xml, 1);
        $xml = preg_replace(
            '#</prod>#',
            '<rastro><nLote>LT1</nLote><qLote>10.4</qLote><dFab>2026-01-02</dFab><dVal>2027-03-04</dVal></rastro>'
                . '<rastro><nLote>LT2</nLote><qLote>2</qLote><dFab>2026-05-06</dFab><dVal>2027-07-08</dVal>'
                . '<vPMC>12.50</vPMC></rastro>'
                . '<med><cProdANVISA>1234567890123</cProdANVISA></med>'
                . '<nFCI>A1B2C3D4-E5F6-7890-ABCD-EF1234567890</nFCI>'
                . '<veicProd><tpOp>1</tpOp><chassi>9BWZZZ377VT004251</chassi><cCor>04</cCor><xCor>BRANCA</xCor>'
                . '<pot>100</pot><cilin>1600</cilin><pesoL>1000</pesoL><pesoB>1200</pesoB><nSerie>123</nSerie>'
                . '<tpComb>16</tpComb><nMotor>ABC</nMotor><CMT>2</CMT><dist>2500</dist><anoMod>2026</anoMod>'
                . '<anoFab>2025</anoFab><tpPint>S</tpPint><tpVeic>06</tpVeic><espVeic>1</espVeic><VIN>N</VIN>'
                . '<condVeic>1</condVeic><cMod>123456</cMod><cCorDENATRAN>04</cCorDENATRAN><lota>5</lota>'
                . '<tpRest>0</tpRest></veicProd></prod>',
            $xml,
            1
        );
        $danfe = $this->danfe('nfe_rtc_minimo.xml', $xml);
        $faixa = $danfe->faixa(0);

        $this->assertStringContainsString("Açúcar\n cristal", $faixa['nome']);
        $this->assertStringNotContainsString(';', $faixa['nome']);
        $this->assertStringContainsString('ANVISA: 1234567890123', $faixa['info']);
        $this->assertStringContainsString('Lote: LT1', $faixa['info']);
        $this->assertStringContainsString('Quant: 10', $faixa['info']);
        $this->assertStringContainsString('Fab: 02/01/2026', $faixa['info']);
        $this->assertStringContainsString('Val: 04/03/2027', $faixa['info']);
        $this->assertStringContainsString('Lote: LT2', $faixa['info']);
        $this->assertStringContainsString('Quant: 2', $faixa['info']);
        $this->assertStringContainsString('PMC: 12.50', $faixa['info']);
        $this->assertStringContainsString('FCI:A1B2C3D4-E5F6-7890-ABCD-EF1234567890', $faixa['info']);
        $this->assertStringContainsString('CHASSI: 9BWZZZ377VT004251', $faixa['info']);
        $this->assertSame('2,50', $danfe->quantidade(0));
        $this->assertSame('1,2345', $danfe->unitario(0));
    }

    public function testCodigoDoProdutoNaDescricao()
    {
        $pdf = (new DanfeRtc(file_get_contents($this->fixture('nfe_rtc_itens.xml'))))->render();

        $this->assertStringContainsString('1564 - RTC TESTE CST000', (new Parser())->parseContent($pdf)->getText());
    }

    /**
     * Imune (CST 410) não tem gIBSCBS: nada impresso e nenhuma linha reservada.
     */
    public function testItemImuneNaoOcupaLinhasDeTributo()
    {
        $danfe = $this->danfe('nfe_rtc_itens.xml');

        $this->assertSame(['bases' => [], 'aliquotas' => [], 'valores' => []], $danfe->tributos(2));
    }

    /**
     * Seis itens cabem numa folha: a faixa só cresce com o que o item tem.
     */
    public function testNotaDeSeisItensCabeEmUmaFolha()
    {
        $pdf = (new DanfeRtc(file_get_contents($this->fixture('nfe_rtc_itens.xml'))))->render();

        $this->assertSame(1, preg_match_all('#/Type\s*/Page[^s]#', $pdf));
    }

    /**
     * Regime normal: ICMS e IPI entram nos pares do modelo (ICMS/CBS, IBS UF/IPI, IBS MUN/IS).
     */
    public function testItemRegimeNormalSegueOsParesDoModelo()
    {
        $danfe = $this->danfe('nfe_rtc_regime_normal.xml');

        $this->assertSame([
            'bases' => [[['ICMS', '10,00']], [['IBS/CBS', '10,00']], [['IPI', '10,00']]],
            'aliquotas' => [
                [['ICMS', '20,00%'], ['CBS', '0,90%']],
                [['IBS UF', '0,10%'], ['IPI', '10,00%']],
                [['IBS MUN', '0,00%']],
            ],
            'valores' => [
                [['ICMS', '2,00'], ['CBS', '0,09']],
                [['IBS UF', '0,01'], ['IPI', '1,00']],
                [['IBS MUN', '0,00']],
            ],
        ], $danfe->tributos(0));
    }

    /**
     * Monofasia ad valorem (CST 620): base, alíquotas e valores vêm de gIBSCBSMono,
     * e os totais vão para o bloco IBS/CBS/IS (NT 2026.010, item 4.1).
     */
    public function testMonofasiaNoItemENoBlocoIbsCbs()
    {
        $danfe = $this->danfe('nfe_rtc_mono.xml');

        $this->assertSame([
            'bases' => [[['MONO', '620,00']]],
            'aliquotas' => [[['CBS', '0,90%'], ['IBS UF', '0,10%']], [['IBS MUN', '0,00%']]],
            'valores' => [[['CBS', '5,58'], ['IBS UF', '0,62']], [['IBS MUN', '0,00']]],
        ], $danfe->tributos(0));
        $this->assertSame([
            ['VALOR DO IBS MONOFÁSICO', '0,62'],
            ['VALOR DA CBS MONOFÁSICA', '5,58'],
        ], $danfe->mono());
        $this->assertSame([], $danfe->icms());
    }

    /**
     * Retido anteriormente: o XML só traz o valor retido, sem base nem alíquota.
     * gMono zerado nos totais não abre a linha da monofasia.
     */
    public function testMonofasiaRetidaAnteriormente()
    {
        $danfe = $this->danfe('nfe_rtc_mono_ret.xml');

        $this->assertSame([
            'bases' => [],
            'aliquotas' => [],
            'valores' => [[['CBS RET ANT', '2,15']], [['IBS RET ANT', '0,24']]],
        ], $danfe->tributos(0));
        $this->assertSame([], $danfe->mono());
    }

    /**
     * Monofasia ad rem (combustíveis): base em quantidade e alíquota em R$ por unidade.
     * O par que não cabe na coluna vai um por linha, sem encolher a fonte.
     */
    public function testMonofasiaAdRem()
    {
        $danfe = $this->danfe('nfe_rtc_mono_adrem.xml');

        $this->assertSame([
            'bases' => [[['MONO', '1.000 L']], [['RETEN', '500 L']]],
            'aliquotas' => [
                [['CBS', 'R$ 1,0530/L'], ['IBS', 'R$ 0,1170/L']],
                [['CBS RETEN', 'R$ 1,0530/L']],
                [['IBS RETEN', 'R$ 0,1170/L']],
            ],
            'valores' => [
                [['CBS', '1.053,00'], ['IBS', '117,00']],
                [['CBS RETEN', '526,50']],
                [['IBS RETEN', '58,50']],
                [['CBS RET ANT', '90,00']],
                [['IBS RET ANT', '10,00']],
            ],
        ], $danfe->tributos(0));
        $this->assertSame([
            ['VALOR DO IBS MONOFÁSICO', '117,00'],
            ['VALOR DA CBS MONOFÁSICA', '1.053,00'],
            ['VALOR DO IBS MONOFÁSICO POR RETENÇÃO', '58,50'],
            ['VALOR DA CBS MONOFÁSICA POR RETENÇÃO', '526,50'],
        ], $danfe->mono());
    }

    /**
     * Nota com todos os quadros: ICMS ST, FCP, DIFAL, IPI, IS, ISSQN, monofasia de ICMS e
     * de IBS/CBS com retenção, fatura, transporte e volumes, numa folha.
     */
    public function testNotaCompletaPreencheOsCamposCondicionados()
    {
        $danfe = $this->danfe('nfe_rtc_completa.xml');

        $this->assertSame([
            ['VALOR DO FCP', '2,40'],
            ['VALOR DO FCP RETIDO POR ST', '3,36'],
            ['VALOR DO DIFAL NA UF DE DESTINO', '42,00'],
            ['VALOR DO FCP NA UF DE DESTINO', '10,50'],
            ['BC DO ICMS MONOFÁSICO', '1.000'],
            ['VALOR DO ICMS MONOFÁSICO', '1.120,00'],
            ['BC DO ICMS MONOFÁSICO POR RETENÇÃO', '500'],
            ['VALOR DO ICMS MONOFÁSICO POR RETENÇÃO', '560,00'],
        ], $danfe->icms());
        $this->assertSame([
            ['VALOR DO IBS MONOFÁSICO', '6,00'],
            ['VALOR DA CBS MONOFÁSICA', '54,00'],
            ['VALOR DO IBS MONOFÁSICO POR RETENÇÃO', '3,00'],
            ['VALOR DA CBS MONOFÁSICA POR RETENÇÃO', '27,00'],
        ], $danfe->mono());
        $this->assertSame([
            'bases' => [
                [['ICMS', '525,00']],
                [['IBS/CBS', '500,00']],
                [['IS', '500,00']],
                [['IPI', '500,00']],
            ],
            'aliquotas' => [
                [['ICMS', '12,00%'], ['CBS', '0,90%']],
                [['IBS UF', '0,10%'], ['IPI', '10,00%']],
                [['IBS MUN', '0,00%'], ['IS', '5,00%']],
            ],
            'valores' => [
                [['ICMS', '63,00'], ['CBS', '4,50']],
                [['IBS UF', '0,50'], ['IPI', '50,00']],
                [['IBS MUN', '0,00'], ['IS', '25,00']],
            ],
        ], $danfe->tributos(0));
        $this->assertSame([
            'bases' => [[['MONO', '6.000,00']], [['RETEN', '3.000,00']]],
            'aliquotas' => [
                [['CBS', '0,90%'], ['IBS UF', '0,10%']],
                [['IBS MUN', '0,00%']],
                [['CBS RETEN', '0,90%']],
                [['IBS RETEN', '0,10%']],
            ],
            'valores' => [
                [['CBS', '54,00'], ['IBS UF', '6,00']],
                [['IBS MUN', '0,00']],
                [['CBS RETEN', '27,00'], ['IBS RETEN', '3,00']],
            ],
        ], $danfe->tributos(2));

        $pdf = (new DanfeRtc(file_get_contents($this->fixture('nfe_rtc_completa.xml'))))->render();
        $this->assertSame(1, preg_match_all('#/Type\s*/Page[^s]#', $pdf));
    }

    /**
     * Paisagem (tpImp=2, leiaute alternativo da NT): os quadros não cabem com os itens na folha 1;
     * a grade vai inteira para a folha 2, sem nada desenhado por cima, e o ISSQN vem antes do transportador.
     */
    public function testPaisagemComTodosOsQuadrosLevaOsItensParaAFolhaSeguinte()
    {
        $pdf = (new DanfeRtc($this->paisagem('nfe_rtc_completa.xml')))->render();
        $folhas = (new Parser())->parseContent($pdf)->getPages();

        $this->assertCount(2, $folhas);
        $folha1 = $folhas[0]->getText();
        $this->assertStringNotContainsString('DADOS DOS PRODUTOS', $folha1);
        $this->assertLessThan(strpos($folha1, 'TRANSPORTADOR'), strpos($folha1, 'CÁLCULO DO ISSQN'));
        $this->assertStringContainsString('SERVICO DE INSTALACAO', $folhas[1]->getText());
    }

    /**
     * Paisagem: a grade da folha 1 desce até os dados adicionais e não reserva espaço para as
     * formas de pagamento, que só aparecem com exibirTextoFatura. A linha do ICMS deixa a faixa
     * mais alta, então os últimos itens seguem na folha 2.
     */
    public function testPaisagemAproveitaAFolhaUm()
    {
        $pdf = (new DanfeRtc($this->paisagem('nfe_rtc_itens.xml')))->render();
        $folhas = (new Parser())->parseContent($pdf)->getPages();

        $this->assertCount(2, $folhas);
        $this->assertStringContainsString('RTC TESTE CST000', $folhas[0]->getText());
        $this->assertStringNotContainsString('RTC TESTE CST830', $folhas[0]->getText());
        $this->assertStringContainsString('RTC TESTE CST830', $folhas[1]->getText());
    }

    /**
     * Mais de 7 duplicatas viram uma linha de aviso, mas a Danfe reserva 12 mm por grupo de 7:
     * a folha 1 usa a altura que sobra de fato, sem mandar o item sozinho para a folha 2.
     */
    public function testPaisagemComDuplicatasUsaAAlturaQueSobra()
    {
        $dups = '';
        for ($i = 1; $i <= 8; $i++) {
            $dups .= "<dup><nDup>00$i</nDup><dVenc>2026-12-0$i</dVenc><vDup>1.00</vDup></dup>";
        }
        $xml = str_replace(
            '<pag>',
            '<cobr><fat><nFat>1</nFat><vOrig>8.00</vOrig><vDesc>0.00</vDesc><vLiq>8.00</vLiq></fat>'
                . $dups . '</cobr><pag>',
            $this->paisagem('nfe_rtc_mono.xml')
        );
        $folhas = (new Parser())->parseContent((new DanfeRtc($xml))->render())->getPages();

        $this->assertCount(1, $folhas);
        $this->assertStringContainsString('RTC TESTE MONO01', $folhas[0]->getText());
    }

    /**
     * infAdProd longo (lista de IMEIs) quebra em linhas mantendo os 5 pt, em vez de encolher
     * a fonte até caber numa linha só.
     */
    public function testInfAdProdLongoQuebraEmLinhas()
    {
        $imeis = [];
        for ($i = 10; $i <= 23; $i++) {
            $imeis[] = '3512345000000' . $i;
        }
        $xml = preg_replace(
            '#<vItem>#',
            '<infAdProd>' . implode('; ', $imeis) . '</infAdProd><vItem>',
            file_get_contents($this->fixture('nfe_rtc_itens.xml')),
            1
        );
        $texto = (new Parser())->parseContent((new DanfeRtc($xml))->render())->getText();

        $linhas = array_values(array_filter(explode("\n", $texto), function ($linha) {
            return strpos($linha, '35123450000') !== false;
        }));
        $this->assertGreaterThan(1, count($linhas));
        $this->assertStringContainsString('351234500000010', $linhas[0]);
        $this->assertStringNotContainsString('351234500000023', $linhas[0]);
    }

    private function paisagem($nome)
    {
        return preg_replace('#<tpImp>\d</tpImp>#', '<tpImp>2</tpImp>', file_get_contents($this->fixture($nome)));
    }

    private function fixture($nome = 'nfe_rtc_minimo.xml')
    {
        return __DIR__ . '/fixtures/xml/' . $nome;
    }

    private function danfe($nome, $xml = null)
    {
        $conteudo = $xml === null ? file_get_contents($this->fixture($nome)) : $xml;

        return new class ($conteudo) extends DanfeRtc {
            public function tributos($i)
            {
                $this->render(); // os pares de tributo são medidos na fonte do PDF
                return $this->tributosItem($this->det->item($i));
            }

            public function identificacao($i)
            {
                return $this->linhasIdentificacao($this->det->item($i));
            }

            public function faixa($i)
            {
                $this->render();
                return $this->medidaFaixa($this->det->item($i));
            }

            public function quantidade($i)
            {
                $prod = $this->det->item($i)->getElementsByTagName('prod')->item(0);

                return $this->textoCasas($prod, 'qCom');
            }

            public function unitario($i)
            {
                $prod = $this->det->item($i)->getElementsByTagName('prod')->item(0);

                return $this->textoCasas($prod, 'vUnCom');
            }

            public function mono()
            {
                return $this->camposMonoOpcionais();
            }

            public function icms()
            {
                return $this->camposIcmsOpcionais();
            }
        };
    }
}

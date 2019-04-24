<?php

namespace Cnab\Retorno\Cnab240;

use \Cnab\Retorno\IArquivo;
use \Cnab\Retorno\IDetalhe;
use \Cnab\Format\Linha;

class Detalhe extends Linha implements IDetalhe
{
    public $codigo_banco;
    public $arquivo;

    public $segmento_t;
    public $segmento_u;
    public $segmento_w;
    public $segmento_a;
    public $segmento_z;

    private $tipoRetorno;

    public function __construct(IArquivo $arquivo, $tipoRetorno = 'boleto')
    {
        $this->codigo_banco = $arquivo->codigo_banco;
        $this->arquivo = $arquivo;
        $this->tipoRetorno = $tipoRetorno;
    }

    /**
     * Retorno se é para dar baixa no boleto.
     *
     * @return bool
     */
    public function isBaixa()
    {
        $codigo_movimento = $this->segmento_t->codigo_movimento;

        return self::isBaixaStatic($codigo_movimento);
    }

    public static function isBaixaStatic($codigo_movimento)
    {
        $tipo_baixa = array(6, 9, 17, 25);
        $codigo_movimento = (int) $codigo_movimento;
        if (in_array($codigo_movimento, $tipo_baixa)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Retorno se é uma baixa rejeitada.
     *
     * @return bool
     */
    public function isBaixaRejeitada()
    {
        $tipo_baixa = array(3, 26, 30);
        $codigo_movimento = (int) $this->segmento_t->codigo_movimento;
        if (in_array($codigo_movimento, $tipo_baixa)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Identifica o tipo de detalhe, se por exemplo uma taxa de manutenção.
     *
     * @return int
     */
    public function getCodigo()
    {
        return (int) $this->segmento_t->codigo_movimento;
    }

    /**
     * Retorna o valor recebido em conta.
     *
     * @return float
     */
    public function getValorRecebido()
    {
        return $this->segmento_u->valor_liquido;
    }

    /**
     * Retorna o valor do título.
     *
     * @return float
     */
    public function getValorTitulo()
    {
        return $this->segmento_t->valor_titulo;
    }

    /**
     * Retorna o valor do pago.
     *
     * @return float
     */
    public function getValorPago()
    {
        if($this->tipoRetorno == 'TED') {
            return $this->segmento_a->valor_real;
        }
        return $this->segmento_u->valor_pago;
    }

    /**
     * Retorna o valor da tarifa.
     *
     * @return float
     */
    public function getValorTarifa()
    {
        return $this->segmento_t->valor_tarifa;
    }

    /**
     * Retorna o valor do Imposto sobre operações financeiras.
     *
     * @return float
     */
    public function getValorIOF()
    {
        return $this->segmento_u->valor_iof;
    }

    /**
     * Retorna o valor dos descontos concedido (antes da emissão).
     *
     * @return Double;
     */
    public function getValorDesconto()
    {
        return $this->segmento_u->valor_desconto;
    }

    /**
     * Retorna o valor dos abatimentos concedidos (depois da emissão).
     *
     * @return float
     */
    public function getValorAbatimento()
    {
        return $this->segmento_u->valor_abatimento;
    }

    /**
     * Retorna o valor de outras despesas.
     *
     * @return float
     */
    public function getValorOutrasDespesas()
    {
        return $this->segmento_u->valor_outras_despesas;
    }

    /**
     * Retorna o valor de outros creditos.
     *
     * @return float
     */
    public function getValorOutrosCreditos()
    {
        return $this->segmento_u->valor_outros_creditos;
    }

    /**
     * Retorna o número do documento do boleto.
     *
     * @return string
     */
    public function getNumeroDocumento()
    {
        $numero_documento = $this->segmento_t->numero_documento;
        if (trim($numero_documento, '0') == '') {
            return;
        }

        return $numero_documento;
    }

    /**
     * Retorna o nosso número do boleto.
     *
     * @return string
     */
    public function getNossoNumero()
    {
        $nossoNumero = 0;

        if($this->tipoRetorno == 'boleto') {
            $nossoNumero = $this->segmento_t->nosso_numero;

            if ($this->codigo_banco == 1) {
                $nossoNumero = preg_replace(
                    '/^'.strval($this->arquivo->getCodigoConvenio()).'/',
                    '',
                    $nossoNumero
                );
            }

            if (in_array($this->codigo_banco, array(\Cnab\Banco::SANTANDER))) {
                // retira o dv
                $nossoNumero = substr($nossoNumero, 0, -1);
            }
        } elseif($this->tipoRetorno == 'TED') {
            $nossoNumero = $this->segmento_a->numero_documento;
        }


        return $nossoNumero;
    }

    public function getAutenticacaoEletronica()
    {
        $autenticaEletronica = '';
        if ($this->tipoRetorno == 'TED') {
            $autenticaEletronica = $this->segmento_z->autenticacao_eletronica;
        }

        return $autenticaEletronica;
    }

    /**
     * Retorna o objeto \DateTime da data de vencimento do boleto.
     *
     * @return \DateTime
     */
    public function getDataVencimento()
    {
        $data = $this->segmento_t->data_vencimento ? \DateTime::createFromFormat('dmY', sprintf('%08d', $this->segmento_t->data_vencimento)) : false;
        if ($data) {
            $data->setTime(0, 0, 0);
        }

        return $data;
    }

    /**
     * Retorna a data em que o dinheiro caiu na conta.
     *
     * @return \DateTime
     */
    public function getDataCredito()
    {
        $data = false;
        if($this->tipoRetorno == 'boleto') {
            $data = $this->segmento_u->data_credito ? \DateTime::createFromFormat('dmY', sprintf('%08d', $this->segmento_u->data_credito)) : false;
        }

        if($this->tipoRetorno == 'TED') {
            $data = $this->segmento_a->data_real ? \DateTime::createFromFormat('dmY', sprintf('%08d', $this->segmento_a->data_real)) : false;
        }

        if ($data) {
            $data->setTime(0, 0, 0);
        }

        return $data;
    }

    /**
     * Retorna o valor de juros e mora.
     */
    public function getValorMoraMulta()
    {
        return $this->segmento_u->valor_acrescimos;
    }

    /**
     * Retorna a data da ocorrencia, o dia do pagamento.
     *
     * @return \DateTime
     */
    public function getDataOcorrencia()
    {
        $data = $this->segmento_u->data_ocorrencia ? \DateTime::createFromFormat('dmY', sprintf('%08d', $this->segmento_u->data_ocorrencia)) : false;
        if ($data) {
            $data->setTime(0, 0, 0);
        }

        return $data;
    }

    /**
     * Retorna o número da carteira do boleto.
     *
     * @return string
     */
    public function getCarteira()
    {
        if ($this->codigo_banco == 104) {
            /*
            É formado apenas o código da carteira
            Código da Carteira
            Código adotado pela FEBRABAN, para identificar a característica dos títulos dentro das modalidades de
            cobrança existentes no banco.
            ‘1’ = Cobrança Simples
            ‘3’ = Cobrança Caucionada
            ‘4’ = Cobrança Descontada
            O Código ‘1’ Cobrança Simples deve ser obrigatoriamente informado nas modalidades Cobrança Simples
            e Cobrança Rápida.
            */
            return;
        } elseif ($this->segmento_t->existField('carteira')) {
            return $this->segmento_t->carteira;
        } else {
            return;
        }
    }

    /**
     * Retorna o número da agencia do boleto.
     *
     * @return string
     */
    public function getAgencia()
    {
        return $this->segmento_t->agencia_mantenedora;
    }

    /**
     * Retorna o número da agencia do boleto.
     *
     * @return string
     */
    public function getAgenciaDv()
    {
        return $this->segmento_t->agencia_dv;
    }

    /**
     * Retorna a agencia cobradora.
     *
     * @return string
     */
    public function getAgenciaCobradora()
    {
        return $this->segmento_t->agencia_cobradora;
    }

    /**
     * Retorna a o dac da agencia cobradora.
     *
     * @return string
     */
    public function getAgenciaCobradoraDac()
    {
        return $this->segmento_t->agencia_cobradora_dac;
    }

    /**
     * Retorna o numero sequencial.
     *
     * @return Integer;
     */
    public function getNumeroSequencial()
    {
        return $this->segmento_t->numero_sequencial_lote;
    }

    /**
     * Retorna o nome do código.
     *
     * @return string
     */
    public function getCodigoNome()
    {
        $codigo = (int) $this->getCodigo();

        $table = array(
             2 => 'Entrada Confirmada',
             3 => 'Entrada Rejeitada',
             4 => 'Transferência de Carteira/Entrada',
             5 => 'Transferência de Carteira/Baixa',
             6 => 'Liquidação',
             9 => 'Baixa',
            12 => 'Confirmação Recebimento Instrução de Abatimento',
            13 => 'Confirmação Recebimento Instrução de Cancelamento Abatimento',
            14 => 'Confirmação Recebimento Instrução Alteração de Vencimento',
            17 => 'Liquidação Após Baixa ou Liquidação Título Não Registrado',
            19 => 'Confirmação Recebimento Instrução de Protesto',
            20 => 'Confirmação Recebimento Instrução de Sustação/Cancelamento de Protesto',
            23 => 'Remessa a Cartório (Aponte em Cartório)',
            24 => 'Retirada de Cartório e Manutenção em Carteira',
            25 => 'Protestado e Baixado (Baixa por Ter Sido Protestado)',
            26 => 'Instrução Rejeitada',
            27 => 'Confirmação do Pedido de Alteração de Outros Dados',
            28 => 'Débito de Tarifas/Custas',
            30 => 'Alteração de Dados Rejeitada',
            36 => 'Confirmação de envio de e-mail/SMS',
            37 => 'Envio de e-mail/SMS rejeitado',
            43 => 'Estorno de Protesto/Sustação',
            44 => 'Estorno de Baixa/Liquidação',
            45 => 'Alteração de dados',
            51 => 'Título DDA reconhecido pelo sacado',
            52 => 'Título DDA não reconhecido pelo sacado',
            53 => 'Título DDA recusado pela CIP',
        );

        if (array_key_exists($codigo, $table)) {
            return $table[$codigo];
        } else {
            return 'Desconhecido';
        }
    }

    /**
     * Retorna o código de liquidação, normalmente usado para
     * saber onde o cliente efetuou o pagamento.
     *
     * @return string
     */
    public function getCodigoLiquidacao()
    {
        // @TODO: Resgatar o código de liquidação
        return;
    }

    /**
     * Retorna a descrição do código de liquidação, normalmente usado para
     * saber onde o cliente efetuou o pagamento.
     *
     * @return string
     */
    public function getDescricaoLiquidacao()
    {
        // @TODO: Resgator descrição do código de liquidação
        return;
    }

    public function dump()
    {
        $dump = PHP_EOL;
        $dump .= '== SEGMENTO T ==';
        $dump .= PHP_EOL;
        $dump .= $this->segmento_t->dump();
        $dump .= '== SEGMENTO U ==';
        $dump .= PHP_EOL;
        $dump .= $this->segmento_u->dump();

        if ($this->segmento_w) {
            $dump .= '== SEGMENTO W ==';
            $dump .= PHP_EOL;
            $dump .= $this->segmento_w->dump();
        }

        return $dump;
    }

    public function isDDA()
    {
        // @TODO: implementar funçao isDDA no Cnab240
    }

    public function getAlegacaoPagador()
    {
        // @TODO: implementar funçao getAlegacaoPagador no Cnab240
    }

    public function getCodigoOcorrencia() {
        return $this->segmento_a->ocorencias;
    }

    public static function getOcorrencia($codigo) {
        $ocorrencias = array(
            '00' => 'PAGAMENTO EFETUADO'
            ,'AE' => 'DATA DE PAGAMENTO ALTERADA'
            ,'AG' => 'NÚMERO DO LOTE INVÁLIDO'
            ,'AH' => 'NÚMERO SEQUENCIAL DO REGISTRO NO LOTE INVÁLIDO'
            ,'AI' => 'PRODUTO DEMONSTRATIVO DE PAGAMENTO NÃO CONTRATADO'
            ,'AJ' => 'TIPO DE MOVIMENTO INVÁLIDO'
            ,'AL' => 'CÓDIGO DO BANCO FAVORECIDO INVÁLIDO'
            ,'AM' => 'AGÊNCIA DO FAVORECIDO INVÁLIDA'
            ,'AN' => 'CONTA CORRENTE DO FAVORECIDO INVÁLIDA / CONTA INVESTIMENTO EXTINTA EM 30/04/2011'
            ,'AO' => 'NOME DO FAVORECIDO INVÁLIDO'
            ,'AP' => 'DATA DE PAGAMENTO / DATA DE VALIDADE / HORA DE LANÇAMENTO /ARRECADAÇÃO / APURAÇÃO INVÁLIDA'
            ,'AQ' => 'QUANTIDADE DE REGISTROS MAIOR QUE 999999'
            ,'AR' => 'VALOR ARRECADADO / LANÇAMENTO INVÁLIDO'
            ,'BC' => 'NOSSO NÚMERO INVÁLIDO'
            ,'BD' => 'PAGAMENTO AGENDADO'
            ,'BE' => 'PAGAMENTO AGENDADO COM FORMA ALTERADA PARA OP'
            ,'BI' => 'CNPJ / CPF DO FAVORECIDO NO SEGMENTOJ-52 ou B INVÁLIDO'
            ,'BL' => 'VALOR DA PARCELA INVÁLIDO'
            ,'CD' => 'CNPJ / CPF INFORMADO DIVERGENTE DO CADASTRADO'
            ,'CE' => 'PAGAMENTO CANCELADO'
            ,'CF' => 'VALOR DO DOCUMENTO INVÁLIDO'
            ,'CG' => 'VALOR DO ABATIMENTO INVÁLIDO'
            ,'CH' => 'VALOR DO DESCONTO INVÁLIDO'
            ,'CI' => 'CNPJ / CPF / IDENTIFICADOR / INSCRIÇÃO ESTADUAL / INSCRIÇÃO NO CAD / ICMS INVÁLIDO'
            ,'CJ' => 'VALOR DA MULTA INVÁLIDO'
            ,'CK' => 'TIPO DE INSCRIÇÃO INVÁLIDA'
            ,'CL' => 'VALOR DO INSS INVÁLIDO'
            ,'CM' => 'VALOR DO COFINS INVÁLIDO'
            ,'CN' => 'CONTA NÃO CADASTRADA'
            ,'CO' => 'VALOR DE OUTRAS ENTIDADES INVÁLIDO'
            ,'CP' => 'CONFIRMAÇÃO DE OP CUMPRIDA'
            ,'CQ' => 'SOMA DAS FATURAS DIFERE DO PAGAMENTO'
            ,'CR' => 'VALOR DO CSLL INVÁLIDO'
            ,'CS' => 'DATA DE VENCIMENTO DA FATURA INVÁLIDA'
            ,'DA' => 'NÚMERO DE DEPEND. SALÁRIO FAMILIA INVALIDO'
            ,'DB' => 'NÚMERO DE HORAS SEMANAIS INVÁLIDO'
            ,'DC' => 'SALÁRIO DE CONTRIBUIÇÃO INSS INVÁLIDO'
            ,'DD' => 'SALÁRIO DE CONTRIBUIÇÃO FGTS INVÁLIDO'
            ,'DE' => 'VALOR TOTAL DOS PROVENTOS INVÁLIDO'
            ,'DF' => 'VALOR TOTAL DOS DESCONTOS INVÁLIDO'
            ,'DG' => 'VALOR LÍQUIDO NÃO NUMÉRICO'
            ,'DH' => 'VALOR LIQ. INFORMADO DIFERE DO CALCULADO'
            ,'DI' => 'VALOR DO SALÁRIO-BASE INVÁLIDO'
            ,'DJ' => 'BASE DE CÁLCULO IRRF INVÁLIDA'
            ,'DK' => 'BASE DE CÁLCULO FGTS INVÁLIDA'
            ,'DL' => 'FORMA DE PAGAMENTO INCOMPATÍVEL COM HOLERITE'
            ,'DM' => 'E-MAIL DO FAVORECIDO INVÁLIDO'
            ,'DV' => 'DOC / TED DEVOLVIDO PELO BANCO FAVORECIDO'
            ,'D0' => 'FINALIDADE DO HOLERITE INVÁLIDA'
            ,'D1' => 'MÊS DE COMPETENCIA DO HOLERITE INVÁLIDA'
            ,'D2' => 'DIA DA COMPETENCIA DO HOLETITE INVÁLIDA'
            ,'D3' => 'CENTRO DE CUSTO INVÁLIDO'
            ,'D4' => 'CAMPO NUMÉRICO DA FUNCIONAL INVÁLIDO'
            ,'D5' => 'DATA INÍCIO DE FÉRIAS NÃO NUMÉRICA'
            ,'D6' => 'DATA INÍCIO DE FÉRIAS INCONSISTENTE'
            ,'D7' => 'DATA FIM DE FÉRIAS NÃO NUMÉRICO'
            ,'D8' => 'DATA FIM DE FÉRIAS INCONSISTENTE'
            ,'D9' => 'NÚMERO DE DEPENDENTES IR INVÁLIDO'
            ,'EM' => 'CONFIRMAÇÃO DE OP EMITIDA'
            ,'EX' => 'DEVOLUÇÃO DE OP NÃO SACADA PELO FAVORECIDO'
            ,'E0' => 'TIPO DE MOVIMENTO HOLERITE INVÁLIDO'
            ,'E1' => 'VALOR 01 DO HOLERITE / INFORME INVÁLIDO'
            ,'E2' => 'VALOR 02 DO HOLERITE / INFORME INVÁLIDO'
            ,'E3' => 'VALOR 03 DO HOLERITE / INFORME INVÁLIDO'
            ,'E4' => 'VALOR 04 DO HOLERITE / INFORME INVÁLIDO'
        );

        if(key_exists($codigo, $ocorrencias)) {
            return $ocorrencias[$codigo];
        }

        return false;
    }
}

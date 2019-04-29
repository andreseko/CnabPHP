<?php

namespace Cnab\Remessa\Cnab240;

use \Cnab\Remessa\IArquivo;

class Detalhe
{
    public $segmento_p = NULL;
    public $segmento_q = NULL;
    public $segmento_r = NULL;
    public $segmento_a = NULL;
//    public $segmento_z = NULL;

    public $last_error;

    public function __construct(IArquivo $arquivo, $tipo_remessa)
    {
        if($tipo_remessa == 'boleto') {
            $this->segmento_p = new SegmentoP($arquivo);
            $this->segmento_q = new SegmentoQ($arquivo);
            $this->segmento_r = new SegmentoR($arquivo);
        }

        if($tipo_remessa == 'TED') {
            $this->segmento_a = new SegmentoA($arquivo);
//            $this->segmento_z = new SegmentoZ($arquivo);
        }
    }

    public function validate()
    {
        $this->last_error = null;
        foreach ($this->listSegmento() as $segmento) {
            if (!$segmento->validate()) {
                $this->last_error = get_class($segmento).': '.$segmento->last_error;
            }
        }

        return is_null($this->last_error);
    }

    /**
     * Lista todos os segmentos deste detalhe.
     *
     * @return array
     */
    public function listSegmento()
    {
        $segmentos = array();

        if(!is_null($this->segmento_p)) {
            $segmentos[] = $this->segmento_p;
        }
        if(!is_null($this->segmento_q)) {
            $segmentos[] = $this->segmento_q;
        }
        if(!is_null($this->segmento_r)) {
            $segmentos[] = $this->segmento_r;
        }
        if(!is_null($this->segmento_a)) {
            $segmentos[] = $this->segmento_a;
        }

//        if (!is_null($this->segmento_z)) {
//            $segmentos[] = $this->segmento_z;
//        }

        return $segmentos;
    }

    /**
     * Retorna todas as linhas destes detalhes.
     *
     * @return string
     */
    public function getEncoded()
    {
        $text = array();
        foreach ($this->listSegmento() as $segmento) {
            $text[] = $segmento->getEncoded();
        }

        return implode(Arquivo::QUEBRA_LINHA, $text);
    }
}

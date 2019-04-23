<?php

namespace Cnab\Retorno\Cnab240;

use Cnab\Format\Linha;
use Cnab\Retorno\IArquivo;
use \Cnab\Format\YamlLoad;

class SegmentoZ extends Linha
{
    public function __construct(IArquivo $arquivo)
    {
        $yamlLoad = new YamlLoad($arquivo->codigo_banco, $arquivo->layoutVersao);
        $yamlLoad->load($this, 'cnab240', 'retorno/detalhe_segmento_z');
    }
}
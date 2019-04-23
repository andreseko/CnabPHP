<?php

namespace Cnab\Remessa\Cnab240;

use Cnab\Format\Linha;
use Cnab\Remessa\IArquivo;
use \Cnab\Format\YamlLoad;

class SegmentoZ extends Linha
{
    public function __construct(IArquivo $arquivo)
    {
        $yamlLoad = new YamlLoad($arquivo->codigo_banco, $arquivo->layoutVersao);
        $yamlLoad->load($this, 'cnab240', 'remessa/detalhe_segmento_z');
    }
}
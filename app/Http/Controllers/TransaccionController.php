<?php

namespace App\Http\Controllers;

use App\Traits\ValidatorTrait;

/**
 * Clase usada para servir las funciones de este servicio "User"
 */
class TransaccionController
{

    use ValidatorTrait;
    /**
     * recargarSaldo: requiere de documento, email y valor, retorna status: true y confirmación del valor recargado, ó devuelve un error.
     *
     * @param string $documento
     * @param string $celular
     * @param float $valor
     * @return array
     * @throws SoapFault
     */
    public function recargarSaldo($documento, $celular, $valor)
    {
        
    }
}
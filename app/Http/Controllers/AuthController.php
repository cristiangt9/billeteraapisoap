<?php

namespace App\Http\Controllers;
/**
 * Clase usada para servir las funciones de este servicio "User"
 */
class AuthController
{
    /**
     * Registro: requiere de documento, nombres, email y celular, retorna status: true y token, ó devuelve un status: false y el error.
     *
     * @param string $documento
     * @param string $nombres
     * @param string $email
     * @param string $celular
     * @return array
     * @throws SoapFault
     */
    public function registro($documento, $nombres, $email, $calular)
    {
        // dd([$documento, $nombres, $email, $calular]);
    }
}
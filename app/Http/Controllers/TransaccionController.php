<?php

namespace App\Http\Controllers;

use App\Models\Transaccion;
use App\Models\User;
use App\Traits\CustomResponseTrait;
use App\Traits\ValidatorTrait;
use Illuminate\Support\Facades\DB;

/**
 * Clase usada para servir las funciones de este servicio "User"
 */
class TransaccionController
{

    use ValidatorTrait, CustomResponseTrait;
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
        $rules = [
            "documento" => "required",
            "celular" => "required",
            "valor" => "required|numeric"
        ];


        $inputs = [
            "documento" => $documento,
            "celular" => $celular,
            "valor" => $valor
        ];
        $validator = $this->validatorInput($inputs, $rules);

        if (!$validator->validated) {
            return $this->defaultResponseWithoutData('false', 'Datos faltantes', 'Uno o mas datos son invalidos', $validator->errors, 422);
        }

        $user = User::where('documento', $documento)->where('celular', $celular)->first();

        if (is_null($user)) {
            return $this->defaultResponseWithoutData('false', 'Credenciales Invalidas', 'Las credenciales proporcionadas son invalidas', $validator->errors, 200);
        }
        try {
            DB::beginTransaction();
            $transaccion = new Transaccion();
            $transaccion->tipo = 'recarga';
            $transaccion->valor = $valor;
            $transaccion->estado = 'ejecutado';
            $transaccion->user_executer_id =  $user->id;
            $transaccion->save();

            $user->saldo += $valor;
            $user->save();
            DB::commit();
            return $this->defaultResponse('true', 'Recarga Realizada', 'La recarga ha sido registrada satisfactoriamente', [], ['valor' => $transaccion->valor], 201);
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->defaultResponse('false', 'Algo ha fallado', 'Algo ha fallado durante la generación del usuario, por favor contacte a servicio al cliente', [], ['error' => $th], 422);
        }
    }
}
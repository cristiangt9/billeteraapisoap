<?php

namespace App\Http\Controllers;

use App\Models\Transaccion;
use App\Models\User;
use App\Traits\CustomResponseTrait;
use App\Traits\ValidatorTrait;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

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

    /**
     * consultarSaldo: requiere de documento y celular, retorna status: true y saldo, ó devuelve un error.
     *
     * @param string $documento
     * @param string $celular
     * @return array
     * @throws SoapFault
     */
    public function consultarSaldo($documento, $celular)
    {
        $rules = [
            "documento" => "required",
            "celular" => "required",
        ];


        $inputs = [
            "documento" => $documento,
            "celular" => $celular,
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
            $transaccion = new Transaccion();
            $transaccion->tipo = 'recarga';
            $transaccion->estado = 'ejecutado';
            $transaccion->user_executer_id = $user->id;
            $transaccion->save();

            return $this->defaultResponse('true', 'Consulta Realizada', 'La consulta ha sido registrada satisfactoriamente', [], ['saldo' => $user->saldo], 200);
        } catch (\Throwable $th) {

            return $this->defaultResponse('false', 'Algo ha fallado', 'Algo ha fallado durante la generación del usuario, por favor contacte a servicio al cliente', [], ['error' => $th], 422);
        }
    }
    /**
     * solicitud de Pago: token de sesion, valor del pago, del usuario destino: documento y celular, retorna status: true, confirmación de la generacion de la transaccion y correo electronico con el token de confirmación, ó devuelve un error.
     *
     * @param string $token
     * @param float $valor
     * @return array
     * @throws SoapFault
     */
    public function solicitudPago($token, $valor, $documento, $celular)
    {

        $rules = [
            "token" => "required",
            "celular" => "requerido",
            "documento" => "requerido",
            "valor" => "required|numeric"
        ];


        $inputs = [
            "token" => $token,
            "valor" => $valor
        ];
        $validator = $this->validatorInput($inputs, $rules);

        if (!$validator->validated) {
            return $this->defaultResponseWithoutData('false', 'Datos faltantes', 'Uno o mas datos son invalidos', $validator->errors, 422);
        }

        $tokenRegister = PersonalAccessToken::findToken($token);

        if (is_null($tokenRegister)) {
            return $this->defaultResponseWithoutData('false', 'Credenciales Invalidas o vencidas', 'Las credenciales proporcionadas son invalidas o vencidas', $validator->errors, 401);
        }

        $user_payer = User::find($tokenRegister->tokenable_id);
        if (is_null($user_payer)) {
            return $this->defaultResponseWithoutData('false', 'Usuario Cancelado', 'Las credenciales proporcionadas son invalidas o vencidas', [], 401);
        }

        if($user_payer->saldo < $valor) {
            return $this->defaultResponseWithoutData('false', 'Saldo insuficiente', 'El saldo es menor a la cantidad a pagar', [], 401);
        }

        $user_receptor = User::where('documento', $documento)->where('celular', $celular)->first();
        if (is_null($user_receptor)) {
            return $this->defaultResponseWithoutData('false', 'Usuario Receptor no encontrado', 'Las credenciales proporcionadas prodian ser invalidas o vencidas', $validator->errors, 401);
        }

        try {
            DB::beginTransaction();
            // cancelar las transacciones procesando
            Transaccion::where("estado", "procesando")
            ->where("user_executer_id", $user_payer->id)
            ->update(["estado" => "fallido"]);
            
            $transaccion = new Transaccion();
            $transaccion->tipo = 'pago';
            $transaccion->valor = abs($valor);
            $transaccion->estado = 'procesando';
            $transaccion->user_executer_id =  $user_payer->id;
            $transaccion->user_receptor_id =  $user_receptor->id;
            $transaccion->token_usuario =  $token;
            $transaccion->token_confirmacion = $this->generateRandomString(6);
            $transaccion->save();
            
            //enviar correo aqui
            
            DB::commit();
            return $this->defaultResponse('true', 'Solicitud de Pago Realizada', 'Por favor revise su correo, hemos enviado un token de validación a su correo', [], ['codigo' => $transaccion->token_confirmacion], 201);
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->defaultResponse('false', 'Algo ha fallado', 'Algo ha fallado durante la generación del usuario, por favor contacte a servicio al cliente', [], ['error' => $th], 422);
        }
    }

    private function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
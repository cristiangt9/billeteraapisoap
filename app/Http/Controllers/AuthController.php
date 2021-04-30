<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ValidatorTrait;
use Illuminate\Support\Facades\Hash;

/**
 * Clase usada para servir las funciones de este servicio "User"
 */
class AuthController
{
    use ValidatorTrait;
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
    public function registro($documento, $nombres, $email, $celular)
    {
        $rules = [
            "documento" => "required",
            "nombres" => "required",
            "email" => "required|email|unique:users",
            "celular" => "required|unique:users"
        ];
        try {
            $inputs = [
                "documento" => $documento,
                "nombres" => $nombres,
                "email" => $email,
                "celular" => $celular
            ];
            $validator = $this->validatorInput($inputs, $rules);

            if (!$validator->validated) {
                return ['success' => 'false', 'titulo' => 'validacion fallo', 'data' => $validator->errores];
            }

            $user = new User();
            $user->documento = $documento;
            $user->password = Hash::make($documento);
            $user->nombres = $nombres;
            $user->email = $email;
            $user->celular = $celular;
            $user->save();

            $token = $user->createToken('auth_token')->plainTextToken;
            return ['success' => 'true', 'titulo' => 'Usuario Registrado', 'data' => ["token" => $token]];
        } catch (\Throwable $th) {
            return ['success' => 'false', 'titulo' => 'algo fallo', 'data' => ['error' => $th]];
        }
    }

        /**
     * Login: documento y celular, retorna status: true y token, ó error.
     *
     * @param string $documento
     * @param string $celular
     * @return array CustomResponse
     * @throws SoapFault
     */
    public function login($documento, $celular)
    {
        $rules = ["documento" => "required", "celular" => "required"];
        try {
            //validacion
            $inputs = ["documento" => $documento, "celular" => $celular];
            $validator = $this->validatorInput($inputs, $rules);
            if (!$validator->validated) {
                return ['success' => 'false', 'titulo' => 'validacion fallo', 'data' => $validator->errores];
            }

            // procesar
            $user = User::where('documento', $documento)->where('celular', $celular)->first();

            if (is_null($user)) {
                return ['success' => 'false', 'titulo' => 'Credenciales Invalidas', 'data' => []];
            }

            $token = $user->createToken('auth_token')->plainTextToken;
            return ['success' => 'true', 'titulo' => 'Usuario Autenticado', 'data' => ["token" => $token]];
        } catch (\Throwable $th) {

            return ['success' => 'false', 'titulo' => 'algo fallo', 'data' => ['error' => $th]];
        }
    }
}

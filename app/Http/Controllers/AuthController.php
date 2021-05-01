<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\CustomResponseTrait;
use App\Traits\ValidatorTrait;
use Illuminate\Support\Facades\Hash;

/**
 * Clase usada para servir las funciones de este servicio "User"
 */
class AuthController
{
    use ValidatorTrait, CustomResponseTrait;
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
                return $this->defaultResponseWithoutData('false', 'Datos faltantes', 'Uno o mas datos son invalidos', $validator->errors, 422);
            }

            $user = new User();
            $user->documento = $documento;
            $user->password = Hash::make($documento);
            $user->nombres = $nombres;
            $user->email = $email;
            $user->celular = $celular;
            $user->save();
            
            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->defaultResponse('true', 'Usuario Registrado', 'El usuario ha sido registrado satisfactoriamente', [], ['token' => $token], 201);
        } catch (\Throwable $th) {

            return $this->defaultResponse('false', 'Algo ha fallado', 'Algo ha fallado durante la generación del usuario, por favor contacte a servicio al cliente', [], ['error' => $th], 422);
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
                return $this->defaultResponseWithoutData('false', 'Datos faltantes', 'Uno o mas datos son invalidos', $validator->errors, 422);
            }

            // procesar
            $user = User::where('documento', $documento)->where('celular', $celular)->first();

            if (is_null($user)) {
                return $this->defaultResponseWithoutData('false', 'Credenciales Invalidas', 'Las credenciales proporcionadas son invalidas', [], 200);
            }

            $token = $user->createToken('auth_token')->plainTextToken;
            return $this->defaultResponse('true', 'Usuario Autenticado', 'Bienvenido a la billera digital ePayco', [], ['token' => $token], 200);
        } catch (\Throwable $th) {

            return $this->defaultResponse('false', 'Algo ha fallado', 'Algo ha fallado durante la generación del usuario, por favor contacte a servicio al cliente', [], ['error' => $th], 422);
        }
    }
}

<?php

namespace App\Traits;

use Illuminate\Support\Facades\Validator;

trait ValidatorTrait
{

    protected function validatorInput($input, array $rules, $messages = [], $customAtributes = [])
    {

        $validated = false;
        $errors = [];
        try {

            $validator = Validator::make($input, $rules, $messages, $customAtributes);
            if ($validator->fails()) {
                $errors = $validator->errors()->toArray();
            } else {
                $validated = true;
            }
            return (object) ["validated" => $validated, "errors" => $errors];
            
        } catch (\Throwable $th) {
            dd($th);
        }
    }
}

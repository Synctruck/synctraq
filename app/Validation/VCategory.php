<?php
namespace App\Validation;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

class VCategory
{
	public function Insert($request)
	{
		$validator = Validator::make($request->all(),

            [
                "name" => ["required", "unique:category", "max:100"],
            ],
            [
                "name.required" => "El campo es requerido",
                "name.max"  => "Debe ingresar máximo 250 dígitos",
            ]
        );

        if($validator->fails())
        {
            return response()->json(["status" => 422, "message" => "Validation error", "errors" => $validator->errors()]);
        }
        else
        {
            return false;
        }
	}

	public function Edit($request, $id)
	{
        $request->validate(
            [
                "name" => ["required", "unique:category,name,$id", "max:100"],
            ],
            [
                "name.required" => "El campo es requerido",
                "name.max"  => "Debe ingresar máximo 250 dígitos",
            ]
        );
	}
}
?>
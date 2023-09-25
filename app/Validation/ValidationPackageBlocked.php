<?php
namespace App\Validation;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ValidationPackageBlocked{

	public function Insert($request)
	{
		$validator = Validator::make($request->all(),

            [
                "Reference_Number_1" => ["required", "unique:packageblocked"],
                "comment" => ["required"],
            ],
            [
                "Reference_Number_1.required" => "The field is required",
                "Reference_Number_1.unique" => "The Package ID exists",

                "comment.required" => "The field is required",
            ]
        );

        if($validator->fails())
        {
            return $validator->errors();
        }
	}
}
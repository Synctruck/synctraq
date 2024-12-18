<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $role_id = $this->segment(3);

        return [
            'name'=>['required',Rule::unique('role')->ignore(($role_id))],
            'status'=>['required','in:1,0'],
            // 'permissions'=> ['required']
        ];
    }
}

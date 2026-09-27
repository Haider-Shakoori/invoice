<?php
namespace App\Http\Requests\Central;

use App\Rules\AvailableTenantSlug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name'=>['required','string','max:160'],
            'owner_name'=>['required','string','max:120'],
            'owner_email'=>['required','email','max:190'],
            'owner_phone'=>['nullable','string','max:40'],
            'slug'=>['required','string','min:3','max:63','regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',new AvailableTenantSlug],
            'password'=>['required','confirmed',Password::min(10)->letters()->mixedCase()->numbers()],
        ];
    }
}

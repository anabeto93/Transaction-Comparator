<?php

namespace App\Http\Requests;

use App\Rules\ValidCsvTransactionFileRule as ValidFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class TransactionFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if(Auth::check()) {
            return true;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'csv_file1' => ['required','file','mimes:csv,txt','max:102400',new ValidFile()],
            'csv_file2' => ['required','file','mimes:csv,txt','max:102400',new ValidFile()]
        ];
    }
}

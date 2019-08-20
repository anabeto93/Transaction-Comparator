<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FileNotFoundException;

class ValidCsvTransactionFileRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $required_transaction_headers = [
            "ProfileName",
            "TransactionDate",
            "TransactionAmount",
            "TransactionNarrative",
            "TransactionDescription",
            "TransactionID",
            "TransactionType",
            "WalletReference",
        ];

        try{
            //temporarily store the file
            if(app()->environment('testing')) {//dd([$attribute, Storage::url($value)]);
                request()->file($attribute);
                $content = Storage::disk('local')->get($attribute);
            }else {
                request()->file($attribute)->storeAs('tmp',$attribute);
                $content = Storage::disk('local')->get('tmp/'.$attribute);
            }
        }catch (\Exception $exception) {

            return false;
        }

        if(is_string($content)) {
            $first_line = array_slice(explode(',', $content), 0, 8);
            if(count($first_line) !== 8) {
                return false;
            }

            foreach($required_transaction_headers as $index => $header) {
                if(strpos($first_line[$index], $header) === false) {
                    return false;
                }
            }

            return true;
        }

        //delete the temp file before moving on
        if(app()->environment('testing')) {
            Storage::disk('local')->delete($attribute);
        }else {
            Storage::disk('local')->delete($attribute);
        }

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Invalid Transaction CSV file passed.';
    }
}

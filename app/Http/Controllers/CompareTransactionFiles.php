<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionFormRequest;
use Illuminate\Http\Request;

class CompareTransactionFiles extends Controller
{
    public function __invoke(TransactionFormRequest $request)
    {
        dd($request);
    }
}

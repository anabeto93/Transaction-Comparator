<?php

namespace App\Repositories\Transaction;

use Illuminate\Http\Request;

interface TransactionComparatorInterface
{
    public function compare(Request $request) : array;
}
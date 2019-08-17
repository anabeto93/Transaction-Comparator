<?php

namespace App\Repositories\Transaction;

use Illuminate\Http\Request;

class TransactionComparatorRepository implements TransactionComparatorInterface
{
    public function compare(Request $request): array
    {
        //store the files first
        $names = [];
        foreach(['csv_file1','csv_file2'] as $file) {
            $name = now()->timestamp.$file;
            $request->$file->storeAs('transactions', $name);
            array_push($names, $name);
        }

        dd($names);
    }
}
<?php

namespace App\Http\Controllers;

use App\Repositories\Transaction\TransactionComparatorInterface;
use App\Http\Requests\TransactionFormRequest;
use Illuminate\Http\Request;

class CompareTransactionFiles extends Controller
{
    /** @var TransactionComparatorInterface $comparator */
    public $comparator;

    public function __construct(TransactionComparatorInterface $interface)
    {
        $this->comparator = $interface;
    }

    public function __invoke(TransactionFormRequest $request)
    {
        $response = $this->comparator->compare($request);

        return view('home')->withResults($response['results'])->withReports($response['reports'])->withNames($response['names']);
    }
}

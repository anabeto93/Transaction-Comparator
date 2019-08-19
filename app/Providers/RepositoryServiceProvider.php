<?php

namespace App\Providers;

use App\Repositories\Transaction\TransactionComparatorInterface;
use App\Repositories\Transaction\TransactionComparatorRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(
            TransactionComparatorInterface::class,
            TransactionComparatorRepository::class
        );
    }
}
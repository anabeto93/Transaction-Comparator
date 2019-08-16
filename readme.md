# Tutuka Trial Project - Financial Transactions Comparator

## Description
This project is simply to compare transactions received from two different CSV files to see which transactions match exactly and which transactions do not have match. It can also recommend which transactions from the two files closely match but not 100%.

## Setup and Installation
For simplicity sake and time constraint, vagrant homestead will be used to serve this application. You are welcome to use `docker` and please do raise a PR so that the instruction for using `docker` can be added to these steps.

To setup and have this application run on your localhost, follow the steps outlined below.

You can follow the links below to setup homestead on your machine. I wouldn't bore you with that.

[Getting Started With Laravel Homestead](https://scotch.io/tutorials/getting-started-with-laravel-homestead)

This can also be of help if you are on Windows.

[Larvel Homestead Windows 10](https://medium.com/@eaimanshoshi/i-am-going-to-write-down-step-by-step-procedure-to-setup-homestead-for-laravel-5-2-17491a423aa)

[Official Laravel Homestead](https://laravel.com/docs/5.8/homestead)

### Step 1.
Follow the above instructions and add it to a path homestead can serve it from.

Clone the project to a directory and run the following commands.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

### Step 2.
Add the folder/directory path in `~/Homestead/Homestead.yaml` file and also supply your choice of `url` and `database` names. NB. These should reflect in your `.env` file for launching the app.

### Step 3.
Start up homestead. You need patience for this step.

### Step 4.
Edit your `/etc/hosts` file if you are on Linux or Mac. But if you are on windows, open PowerShell or CMD as admin, and run the following command
```bash
notepad drivers/etc/hosts
```

Add the same `url` as specified in `Step 2.` above. You need to map it to your homestead `IP` address. By default, this IP is usually `192.168.10.10` or you can look in the `Homestead.yaml` to find out which one to use.

```bash
192.168.10.10 tutuka.trial.app
```

NB: `tutuka.trial.app` is the url I chose to use, you can use any that you like.

### Test That It Works
Visit `tutuka.trial.app` or your chosen `url` in your browser. In this case,
`https://tutuka.trial.app`.

NB: One of the reasons for choosing `homestead` is because it serves Laravel projects with `SSL/TLS` by default.

## Testing
To test the functionalities of this project, simply run `./phpunit`.

There is a shell script in the root directory which will handle this and you can pass additional arguments to it.

This project is already been tested before deployment using [Travis](https://travis-ci.org/)

## Badges
[![Build Status](https://travis-ci.org/anabeto93/Transaction-Comparator.svg?branch=master)](https://travis-ci.org/anabeto93/Transaction-Comparator)

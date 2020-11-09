<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use App\Message;

class TestBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
	//artisanコマンドで呼び出すときのコマンド名を定義する
    protected $signature = 'batch:test';

    /**
     * The console command description.
     *
     * @var string
     */
	//artisanコマンド一覧の出力時に表示される説明文、必須ではないが設定推奨
    protected $description = 'お試しのバッチ処理';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
	//実際の処理をメソッド内に記述する
    public function handle()
    {
		$user = User::get();
		error_log(print_r($user[0]->name, true) . "\n", 3, '/var/www/html/log.txt');
		echo $user;
		$result = 'バッチテスト';
		error_log(print_r($result, true) . "\n", 3, '/var/www/html/log.txt');
		echo $result;
    }
}

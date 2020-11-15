<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Message;

class MessagesReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'batch:MessagesReset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'メッセージテーブルを空にするバッチ処理です';

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
    public function handle()
    {
		Message::truncate();
		echo "最後まで実行しました\n";
    }
}

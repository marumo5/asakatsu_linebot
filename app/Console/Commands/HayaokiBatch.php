<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use App\Message;
use LINE\LINEBot;
use LINE\LINEBot\Constant\HTTPHeader;
use LINE\LINEBot\SignatureValidator;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use Exception;

class HayaokiBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'batch:hayaoki';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '6時台に起きたかチェックするバッチ処理です';

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
		$messages = Message::get();
		$result = '13時です';
		error_log(print_r($result, true) . "\n", 3, '/var/www/html/log.txt');
		foreach($messages as $message) {
			if (strtotime(date('Y-m-d 6:00:00')) <= strtotime($message->created_at) && strtotime($message->created_at) < strtotime(date('Y-m-d 7:00:00'))) {
				$user = User::find($message->user_id);
				$user->oversleep_check = 1;
				$user->save();
				$result = $user->name . "さん早起きできましたね\n";
			} else {
				$result = $message->user_id . "さんちゃんと起きないと！\n";
			}
			error_log(print_r($result, true) . "\n", 3, '/var/www/html/log.txt');
		}
		$users = User::all();
		foreach($users as $user) {
			echo $user->name;
			if ($user->oversleep_check === 0) {
				$user->oversleeping_times += 1;
				$user->save();
			}
		}
		User::where('oversleep_check', 1)->update(['oversleep_check' => 0]);
//		Message::truncate();
//		error_log(print_r($users, true) . "\n", 3, '/var/www/html/log.txt');
		echo "成功です\n";
    }
}

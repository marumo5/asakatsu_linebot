<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use App\Message;
use LINE\LINEBot;
//use LINE\LINEBot\Constant\HTTPHeader;
//use LINE\LINEBot\SignatureValidator;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
//use Exception;
use Illuminate\Support\Facades\DB;

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
		//明日パス、おはようのメッセージがあったか確認する
		$affiliation_ids = User::groupBy('affiliation_id')->get(['affiliation_id']);
		foreach($affiliation_ids as $affiliation_id) {
			DB::transaction(function() use($affiliation_id) {
				$affiliation_id = $affiliation_id->affiliation_id;
				error_log(print_r($affiliation_id, true) . "\n", 3, '/var/www/html/log.txt');
				$text_message = null;
				$messages = Message::where('affiliation_id', $affiliation_id)->get();
				foreach($messages as $message) {
					if (strtotime('22:00:00') <= strtotime(date('H:i:s', strtotime($message->created_at))) || strtotime(date('H:i:s', strtotime($message->created_at))) < strtotime('7:00:00')) {
						$user = User::find($message->user_id);
						$user->oversleep_check = 1;
						$user->save();
					}
				}
				//寝坊回数を増やす
				$users = User::where('affiliation_id', $affiliation_id)->get();
				foreach($users as $user) {
					if ($user->oversleep_check === 0) {
						$user->oversleeping_times += 1;
						$user->save();
						$text_message .= $user->name . "さん起きてください！！\n";
					}
				}
				//プッシュメッセージの送信
				$lineAccessToken = config('line.line_access_token', "");
				$lineChannelSecret = config('line.line_channel_secret', "");
				$httpClient = new CurlHTTPClient($lineAccessToken);
				$lineBot = new LINEBot($httpClient, ['channelSecret' => $lineChannelSecret]);
				if (!$text_message) {
					$text_message = '寝坊なし！さすがです！！';
				}
				$textMessageBuilder = new TextMessageBuilder($text_message);
				$response = $lineBot->pushMessage($affiliation_id, $textMessageBuilder);
				$response->getHTTPStatus() . ' ' . $response->getRawBody();
				echo $text_message;
				error_log(print_r($text_message, true) . "\n", 3, '/var/www/html/log.txt');
				User::where('oversleep_check', 1)->update(['oversleep_check' => 0]);
			}, 5);
		}
		Message::truncate();
		echo "成功です\n";
    }
		//プッシュメッセージの送信
/*		$lineAccessToken = config('line.line_access_token', "");
		$lineChannelSecret = config('line.line_channel_secret', "");
		$httpClient = new CurlHTTPClient($lineAccessToken);
		$lineBot = new LINEBot($httpClient, ['channelSecret' => $lineChannelSecret]);
		if (!$text_message) {
			$text_message = '寝坊なし！さすがです！！';
		}
		$textMessageBuilder = new TextMessageBuilder($text_message);
		$response = $lineBot->pushMessage($user->user_identifier, $textMessageBuilder);
//			echo $response->getHTTPStatus() . ' ' . $response->getRawBody();
//			echo $text_message;
		error_log(print_r($text_message, true) . "\n", 3, '/var/www/html/log.txt');
	}*/
//以下は必要であれば、書き直す。寝坊回数は聞かれたら答えるようにする.以下は個人ラインに送信する方法
/*		$text_message = null;
		$users = User::all();
		foreach($users as $user) {
			$text_message = $user->name . 'さんの現在の寝坊回数は' . $user->oversleeping_times . "回です！\n";
			$text_message .= $user->name . "さん朝から頑張ってますね！\n今日も最高の１日にしましょう！！";
			//プッシュメッセージの送信
			$lineAccessToken = config('line.line_access_token', "");
			$lineChannelSecret = config('line.line_channel_secret', "");
			$httpClient = new CurlHTTPClient($lineAccessToken);
			$lineBot = new LINEBot($httpClient, ['channelSecret' => $lineChannelSecret]);
			$textMessageBuilder = new TextMessageBuilder($text_message);
			$response = $lineBot->pushMessage($user->user_identifier, $textMessageBuilder);
			echo $response->getHTTPStatus() . ' ' . $response->getRawBody();
			echo $text_message;
			error_log(print_r($text_message, true) . "\n", 3, '/var/www/html/log.txt');
		}*/
}

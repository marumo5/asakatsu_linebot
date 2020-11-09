<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use LINE\LINEBot;
use LINE\LINEBot\Constant\HTTPHeader;
use LINE\LINEBot\SignatureValidator;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use Exception;
use App\User;
use App\Message;
//use Illuminate\Support\Carbon;

class LineController extends Controller
{
	public function webhook (Request $request)
	{
		$lineAccessToken = config('line.line_access_token', "");
		$lineChannelSecret = config('line.line_channel_secret', "");
//		error_log(print_r($lineAccessToken, true) . '\n', 3, '/var/www/html/log.txt');
//		error_log(print_r($lineChannelSecret, true) . '\n', 3, '/var/www/html/log.txt');

		// 署名のチェック
		$signature = $request->headers->get(HTTPHeader::LINE_SIGNATURE);
		if (!SignatureValidator::validateSignature($request->getContent(), $lineChannelSecret, $signature)) {
		//  不正アクセス
			$text = '不正アクセス';
			error_log(print_r($text, true) . '\n', 3, '/var/www/html/log.txt');
			return;
		}

		$httpClient = new CurlHTTPClient ($lineAccessToken);
		$lineBot = new LINEBot($httpClient, ['channelSecret' => $lineChannelSecret]);

		try {
			// イベント取得
			$events = $lineBot->parseEventRequest($request->getContent(), $signature);
			foreach ($events as $event) {
				// 入力した文字取得
				$line_message = $event->getText();
				//メッセージを送信したユーザーのIDを取得
				$user_id = $event->getUserId();
//				error_log(print_r($user_id, true) . '\n', 3, '/var/www/html/log.txt');
				//ユーザーの存在を確認
				$user_exist = User::where('user_identifier', $user_id)->exists();
//				error_log(print_r($user_exist, true) . '\n', 3, '/var/www/html/log.txt');
				if (strcmp($line_message, '朝活に参加します') == 0) {
					//ユーザー情報を取得するメソッドを使用
					$response = $lineBot->getProfile($user_id);
					//取得した情報をJSONデコードする
					$profile = $response->getJSONDecodedBody();
//					error_log(print_r($profile, true) . '\n', 3, '/var/www/html/log.txt');
					$user_display_name = $profile['displayName'];
					//ユーザーが登録済みかどうか判断する
					if ($user_exist) {
//						$user_exist = 'います';
//						error_log(print_r($user_exist, true) . '\n', 3, '/var/www/html/log.txt');
						$replyToken = $event->getReplyToken();
						$text_message = $user_display_name . 'さんは参加登録済みです！朝活頑張りましょう！';
					} else {
//						$user_exist = 'いません';
//						error_log(print_r($user_exist, true) . '\n', 3, '/var/www/html/log.txt');
						$user = new User();
						$input = ['name' => $user_display_name, 'user_identifier' => $user_id, 'oversleeping_times' => 0];
						$result = $user->fill($input)->save();
						error_log(print_r($result, true) . "\n", 3, '/var/www/html/log.txt');
						$replyToken = $event->getReplyToken();
						$text_message = $user_display_name . 'さんようこそ！朝活頑張りましょう！';
					}
					$textMessage = new TextMessageBuilder($text_message);
					$lineBot->replyMessage($replyToken, $textMessage);
				} else {
//					$confirm = 'メッセージ保存';
//					error_log(print_r($confirm, true) . '\n', 3, '/var/www/html/log.txt');
					if ($user_exist) {
						$exist = 'います';
//						error_log(print_r($exist, true) . '\n', 3, '/var/www/html/log.txt');
						$user = User::where('user_identifier', $user_id)->first();
//						error_log(print_r($user->user_identifier, true) . '\n', 3, '/var/www/html/log.txt');
//						$now = date("Y-m-d H:i:s");
//						error_log(print_r(Carbon::now()->timestamp, true) . '\n', 3, '/var/www/html/log.txt');
//						error_log(print_r($now, true) . '\n', 3, '/var/www/html/log.txt');
//						error_log(print_r(gettype($now), true) . '\n', 3, '/var/www/html/log.txt');
//						$message_exist = Message::all();
//						error_log(print_r($message_exist, true) . '\n', 3, '/var/www/html/log.txt');
//						error_log(print_r($user->id, true) . '\n', 3, '/var/www/html/log.txt');
//						error_log(print_r(date("Y-m-d H:i:s"), true) . '\n', 3, '/var/www/html/log.txt');
//						$now = date("Y-m-d H:i:s", Carbon::now()->timestamp);
//						error_log(print_r($now, true) . '\n', 3, '/var/www/html/log.txt');
//						error_log(print_r(gettype($now), true) . '\n', 3, '/var/www/html/log.txt');
//						$use = User::where('user_identifier', $user_id)->get();
//						error_log(print_r($use, true) . '\n', 3, '/var/www/html/log.txt');
//						$messages = Message::all();
//						error_log(print_r($messages, true) . '\n', 3, '/var/www/html/log.txt');
						try {
						$message = new Message();
						$user_id = 3;
						$line_message = "aaa";
						$message->user_id = $user_id;
						$message->message = $line_message;
						error_log(print_r(1, true) . "\n", 3, '/var/www/html/log.txt');
						$result = $message->save();
						} catch (\Exception $e) {
							error_log(print_r($e, true) . "\n", 3, '/var/www/html/log.txt');
						}
						exit;
//						error_log(print_r($message, true) . "\n", 3, '/var/www/html/log.txt');
//						error_log(print_r($result, true) . "\n", 3, '/var/www/html/log.txt');
//						error_log($message . "\n", 3, '/var/www/html/log.txt');
//						$m_input = ['user_id' => $user->id, 'message' => $line_message, 'posted_at' => date("Y-m-d H:i:s", Carbon::now()->timestamp)];
//						$minput = ['user_id' => 1, 'message' => 'メッセージ'];
						$input = ['user_id' => $user_id, 'message' => $line_message];
						error_log(print_r($input, true) . "\n", 3, '/var/www/html/log.txt');
//						$minput = ['user_id' => $user->id, 'message' => $line_message];
						error_log(print_r($input, true) . "\n", 3, '/var/www/html/log.txt');
//						$result = $message->fill($input)->toSql();
//						error_log(print_r($result, true) . '\n', 3, '/var/www/html/log.txt');
						$result = $message->fill($input)->save();
						error_log(print_r($message, true) . "\n", 3, '/var/www/html/log.txt');
						error_log(print_r($result, true) . "\n", 3, '/var/www/html/log.txt');
/*						$result = $message->create([
							'user_id' => $user->id,
							'message' => $line_message,
						]);*/
//						error_log(print_r($result, true) . "\n", 3, '/var/www/html/log.txt');
						$text = 'メッセージをDBに保存';
						$replyToken = $event->getReplyToken();
						$textMessage = new TextMessageBuilder($text);
						$lineBot->replyMessage($replyToken, $textMessage);
					}
				}
			}
		} catch (Exception $e) {
			// TODO 例外
			return;
		}
		return;
	}
}

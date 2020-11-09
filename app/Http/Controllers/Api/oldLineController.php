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

class LineController extends Controller
{
	public function webhook (Request $request)
	{
		$lineAccessToken = config('line.line_access_token', "");
		$lineChannelSecret = config('line.line_channel_secret', "");

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
				//ユーザーの存在を確認
				$user_exist = User::where('user_identifier', $user_id)->exists();
				if (strcmp($line_message, '朝活に参加します') == 0) {
					//ユーザー情報を取得するメソッドを使用
					$response = $lineBot->getProfile($user_id);
					//取得した情報をJSONデコードする
					$profile = $response->getJSONDecodedBody();
					$user_display_name = $profile['displayName'];
					//ユーザーが登録済みかどうか判断する
					if ($user_exist) {
						$replyToken = $event->getReplyToken();
						$text_message = $user_display_name . 'さんは参加登録済みです！朝活頑張りましょう！';
					} else {
						$user = new User();
						$input = ['name' => $user_display_name, 'user_identifier' => $user_id, 'oversleeping_times' => 0];
						$result = $user->fill($input)->toSql();
						$user->fill($input)->save();
						$replyToken = $event->getReplyToken();
						$text_message = $user_display_name . 'さんようこそ！朝活頑張りましょう！';
					}
					$textMessage = new TextMessageBuilder($text_message);
					$lineBot->replyMessage($replyToken, $textMessage);
				} else {
					if ($user_exist) {
						$user = User::where('user_identifier', $user_id)->first();
						$message = new Message();
						$user_id = $user->id;
						error_log(print_r($user_id, true) . "\n", 3, '/var/www/html/log.txt');
						error_log(print_r($line_message, true) . "\n", 3, '/var/www/html/log.txt');
						$message->user_id = $user_id;
						$message->message = $line_message;
						$result = $message->save();
						error_log(print_r($message, true) . "\n", 3, '/var/www/html/log.txt');
						error_log(var_dump($result) . "\n", 3, '/var/www/html/log.txt');
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

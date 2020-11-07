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
						//ユーザーが登録済みの場合
						$replyToken = $event->getReplyToken();
						$text_message = $user_display_name . 'さんは参加登録済みです！朝活頑張りましょう！';
					} else {
						//ユーザーが未登録の場合
						$user = new User();
						$input = ['name' => $user_display_name, 'user_identifier' => $user_id, 'oversleeping_times' => 0];
						$result = $user->fill($input)->save();
						error_log(print_r($result, true) . "\n", 3, '/var/www/html/log.txt');
						$replyToken = $event->getReplyToken();
						$text_message = $user_display_name . 'さんようこそ！朝活頑張りましょう！';
					}
					$textMessage = new TextMessageBuilder($text_message);
					$lineBot->replyMessage($replyToken, $textMessage);
				} elseif (strpos($line_message, '明日') || strpos($line_message, 'パス')) {
					if ($user_exist) {
						$user = User::where('user_identifier', $user_id)->first();
						$user_id = $user->id;
						$message = new Message();
						$input = ['user_id' => $user_id, 'message' => $line_message];
						$message->fill($input)->save();
						$message->save();
						$text = '承知しました。ぐっすり寝てくださいな。';
						$replyToken = $event->getReplyToken();
						$textMessage = new TextMessageBuilder($text);
						$lineBot->replyMessage($replyToken, $textMessage);
					}
				} else {
					//ユーザー朝活に参加している場合はメッセージテーブルに保存する
					if ($user_exist) {
						//最終的に時間を12時から23時に変更する
						//DBへメッセージを保存する時間帯を指定
						if (strtotime(date('H:i:s')) < strtotime('7:00:00') || strtotime('12:00:00') < strtotime(date('H:i:s'))) {
							$result = '時間の範囲';
							error_log(print_r($result, true) . "\n", 3, '/var/www/html/log.txt');
							$user = User::where('user_identifier', $user_id)->first();
							$user_id = $user->id;
							$message = new Message();
							$input = ['user_id' => $user_id, 'message' => $line_message];
							$message->fill($input)->save();
							$message->save();
							$text = 'メッセージをDBに保存';
							$replyToken = $event->getReplyToken();
							$textMessage = new TextMessageBuilder($text);
							$lineBot->replyMessage($replyToken, $textMessage);
						}

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

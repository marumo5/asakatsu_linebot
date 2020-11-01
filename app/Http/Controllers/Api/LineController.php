<?php
a

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use LINE\LINEBot;
use LINE\LINEBot\Constant\HTTPHeader;
use LINE\LINEBot\SignatureValidator;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use Exception;
use App\User as User;

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
			$text = 'エラー';
			error_log(print_r($text, true) . '\n', 3, '/var/www/html/log.txt');
			return;
		}

		$httpClient = new CurlHTTPClient ($lineAccessToken);
		$lineBot = new LINEBot($httpClient, ['channelSecret' => $lineChannelSecret]);

		try {
			// イベント取得
			$events = $lineBot->parseEventRequest($request->getContent(), $signature);
			error_log(print_r($events, true) . '\n', 3, '/var/www/html/log.txt');
			foreach ($events as $event) {
				// 入力した文字取得
				$message = $event->getText();
				if (strcmp($message, '朝活に参加します') == 0) {
					//メッセージを送信したユーザーのIDを取得
					$user_id = $event->getUserId();
					//ユーザー情報を取得するメソッドを使用
					$response = $lineBot->getProfile($user_id);
					//取得した情報をJSONデコードする
					$profile = $response->getJSONDecodedBody();
					$user_display_name = $profile['displayName'];
//					error_log(print_r($user_id, true) . '\n', 3, '/var/www/html/log.txt');
					$user_exist = User::where('user_identifier', $user_id)->exists();
					error_log(print_r($user_exist, true) . '\n', 3, '/var/www/html/log.txt');
					//ユーザーが登録済みかどうか判断する
					if ($user_exist) {
						$replyToken = $event->getReplyToken();
						$message = $user_display_name . 'さんは参加登録済みです！朝活頑張りましょう！';
					} else {
						$user = new User();
						$input = ['name' => $user_display_name, 'user_identifier' => $user_id, 'oversleeping_times' => 0];
						$user->fill($input)->save();
						$replyToken = $event->getReplyToken();
						$message = $user_display_name . 'さんようこそ！朝活頑張りましょう！';
					}
					$textMessage = new TextMessageBuilder($message);
					$lineBot->replyMessage($replyToken, $textMessage);
				}
				$replyToken = $event->getReplyToken();
				$textMessage = new TextMessageBuilder($message);
				$lineBot->replyMessage($replyToken, $textMessage);
			}
		} catch (Exception $e) {
			// TODO 例外
			return;
		}
		return;
	}
}

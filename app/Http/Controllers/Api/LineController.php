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

class LineController extends Controller
{
	public function webhook (Request $request)
	{
		$lineAccessToken = config('line.line_access_token', "");
		$lineChannelSecret = config('line.line_channel_secret', "");

//error_log(print_r($lineAccessToken, true) . '\n', 3, '/var/www/html/log.txt');
//error_log(print_r($lineChannelSecret, true) . '\n', 3, '/var/www/html/log.txt');
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
				if (strcmp($message, '参加します') == 0) {
					$replyToken = $event->getReplyToken();
					$text = '朝活頑張りましょう！';
					$textMessage = new TextMessageBuilder($text);
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

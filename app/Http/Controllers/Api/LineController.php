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
        $lineAccessToken = env('LINE_ACCESS_TOKEN', "");
        $lineChannelSecret = env('LINE_CHANNEL_SECRET', "");

error_log(print_r($lineAccessToken, true) . '\n', 3, '/var/www/html/log.txt');
error_log(print_r($lineChannelSecret, true) . '\n', 3, '/var/www/html/log.txt');
        // 署名のチェック
        $signature = $request->headers->get(HTTPHeader::LINE_SIGNATURE);
        if (!SignatureValidator::validateSignature($request->getContent(), $lineChannelSecret, $signature)) {
            // TODO 不正アクセス
	$text = 'エラー';
	error_log(print_r($text, true) . '\n', 3, '/var/www/html/log.txt');
//	    error_log(print_r($signature, true) . '\n', 3, '/var/www/html/log.txt');
            return;
	}

        $httpClient = new CurlHTTPClient ($lineAccessToken);
        $lineBot = new LINEBot($httpClient, ['channelSecret' => $lineChannelSecret]);

//error_log(print_r($httpClient, true) . '\n', 3, '/var/www/html/log.txt');
//error_log(print_r($lineBot, true) . '\n', 3, '/var/www/html/log.txt');
        try {
            // イベント取得
            $events = $lineBot->parseEventRequest($request->getContent(), $signature);
	    error_log(print_r($events, true) . '\n', 3, '/var/www/html/log.txt');
            foreach ($events as $event) {
                // ログファイルの設定
//               $file = "/var/www/html/asakatsu-linebot/asa_linebot/storage/logs/log.txt";
 //              file_put_contents($file, print_r($event, true) . PHP_EOL, FILE_APPEND);
                // 入力した文字取得
                $message = $event->getText();
                $replyToken = $event->getReplyToken();
                $textMessage = new TextMessageBuilder($message);
                $lineBot->replyMessage($replyToken, $textMessage);
            }
        } catch (Exception $e) {
            // TODO 例外
            return;
        }
//	error_log(print_r($events, true) . '\n', 3, '/var/www/html/asakatsu_linebot/asa_linebot/log.txt');
        return;
    }
}

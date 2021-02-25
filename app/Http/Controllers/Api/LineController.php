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
//デバッグ用
//$result = 'ここまできてるよ';
//error_log(print_r($result, true) . "\n", 3, '/var/www/html/log.txt');

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
				$event_type = $event->getType();
				if ($event_type === 'join') {
					$text_message = "皆さんの朝活を支援する\n\n朝活ラインボットです！\n\nラインボットの説明：\n\n①「朝活に参加します」と言って参加登録してください！\n\n②６時〜７時にメッセージをください！メッセージがない場合は寝坊したことになります！\n\n③次の日どうしても７時までに起きれそうにない場合は、２２時から６時の間に「明日はパス」や「明日は休み」とメッセージをください！特別に朝活を免除します！！\n\n④「寝坊した回数を教えて」と言われたらグループ員の寝坊した回数を教えます！\n\n⑤現在は月〜金（祝日を含む）のみラインボットが稼働します。今後、土日も朝活したいメンバー向けの機能を実装予定です。\n\n⑥７時に寝坊した人の名前を通知します。\n\nそれじゃあ、朝活で人生を豊かにしましょう！！";
				}
				if ($event_type === 'message') {
					// 入力した文字取得
					$line_message = $event->getText();
					//メッセージを送信したユーザーのIDを取得
					$user_identifier = $event->getUserId();
					//所属先IDを取得(groupId or roomId or userId)
					$affiliation_id = $event->getEventSourceId();
					//ユーザーの存在を確認
					$user_exist = User::where('user_identifier', $user_identifier)->where('affiliation_id', $affiliation_id)->exists();
					//朝活への参加処理
					if (strpos($line_message, '朝活') !== false && strpos($line_message, '参加') !== false) {
						//ユーザー情報を取得するメソッドを使用
						$response = $lineBot->getProfile($user_identifier);
						//取得した情報をJSONデコードする
						$profile = $response->getJSONDecodedBody();
						$user_display_name = $profile['displayName'];
						//ユーザーが登録済みかどうか判断する
						if ($user_exist) {
							//ユーザーが登録済みの場合
							$text_message = $user_display_name . 'さんは参加登録済みです！朝活頑張りましょう！';
						} else {
							//ユーザーが未登録の場合
							$user = new User();
							$input = ['name' => $user_display_name, 'user_identifier' => $user_identifier, 'affiliation_id' => $affiliation_id];
							$result = $user->fill($input)->save();
							$text_message = $user_display_name . 'さんようこそ！朝活頑張りましょう！';
						}
					} elseif (strpos($line_message, '祝日') !== false) {
						$text_message = 'ごめんなさい！修正しときます！';
					}
					//メッセージ保存処理
					if ($user_exist) {
						$user = User::where('user_identifier', $user_identifier)->where('affiliation_id', $affiliation_id)->first();
						$user_id = $user->id;
						//明日はパスする処理
						if ((strpos($line_message, 'パス') !== false || strpos($line_message, '休み') !== false) && strpos($line_message, '明日') !== false) {
							if (strtotime(date('H:i:s')) < strtotime('6:00:00') || strtotime('22:00:00') <= strtotime(date('H:i:s'))) {
								$message = new Message();
								$input = ['user_id' => $user_id, 'message' => $line_message, 'affiliation_id' => $affiliation_id];
								$message->fill($input)->save();
								$text_message = '承知しました。ぐっすり寝てくださいな。';
							//以下は必要に応じて使う
	/*						} else {
								$text_message = '今寝ればまだ間に合うよ！！！';*/
							}
						}
						//DBへメッセージを保存する時間帯を指定
						if (strtotime('5:59:00') <= strtotime(date('H:i:s')) && strtotime(date('H:i:s')) < strtotime('7:00:00')) {
							$message = new Message();
							$input = ['user_id' => $user_id, 'message' => $line_message, 'affiliation_id' => $affiliation_id];
							$message->fill($input)->save();
							//以下は必要に応じて使う
//							$text_message = 'おはようございます！';
						}
						//現在の寝坊回数をお知らせする
						if (strpos($line_message, '寝坊') !== false && strpos($line_message, '回数') !== false && strpos($line_message, '教えて') !== false) {
							$text_message = "現在の寝坊回数\n";
							$users = User::where('affiliation_id', $affiliation_id)->get();
							foreach($users as $user) {
								$text_message .= $user->name . 'さんは「' . $user->oversleeping_times . "回」です！\n";
							}
							$text_message .= "朝から頑張ってますね！\n今日も最高の１日にしましょう！！";
						}
					}
				}
				$replyToken = $event->getReplyToken();
				$textMessage = new TextMessageBuilder($text_message);
				$lineBot->replyMessage($replyToken, $textMessage);
			}
		} catch (Exception $e) {
			// TODO 例外
			return;
		}
		return;
	}
}

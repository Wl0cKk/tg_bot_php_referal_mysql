<?php
trait SubscribeLogic {

    public function handleJoinChannelCommand($telegram, $chat_id, $message_id) {
        $tg_key = $this->getKey();
        $channelURL = $this->getURL($tg_key);
        $handleMessage = $this->getPhraseText("join_text", $chat_id);
        $message = str_replace(
            ['{$sum}', '{$chanURL}'],
            [$GLOBALS['joinChannelPay'], $channelURL],
            $handleMessage
        );
        $keyboard = json_encode([
            'inline_keyboard' => [
                [['text' => $this->getPhraseText("checkChannel_button", $chat_id), 'callback_data' => 'check']],
                [['text' => $this->getPhraseText("skipChannel_button", $chat_id), 'callback_data' => 'skip']]
            ]
        ]);
        $content = [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => $message,
            'reply_markup' => $keyboard
        ];
        $telegram->editMessageText($content);
    }

    public function handleSubscribeCheckCommand($telegram, $chat_id, $message_id) {
        $tg_key = $this->getKey();
        $response = $telegram->getChatMember($tg_key, $chat_id);
        $subscriptionStatus = $response['result']['status'];
        if ($subscriptionStatus === 'member' || $subscriptionStatus === 'administrator' || $subscriptionStatus === 'creator') {
            $message = "✅ Проверка прошла! {$GLOBALS['subscribeSumValue']}\nОставайтесь активными и не отписывайтесь от канала в течение 5 дней. Если вы отпишетесь, деньги вернутся.";
        } else {
            $channelURL = $this->getURL($tg_key);
            $message = "❌ Проверить не удалось! Подпишитесь на канал: {$channelURL}";
        }
        $keyboard = json_encode([
            'inline_keyboard' => [
                [['text' => 'Next', 'callback_data' => 'next']],
                [['text' => $this->getPhraseText("checkChannel_button", $chat_id), 'callback_data' => 'check']],
                [['text' => $this->getPhraseText("skipChannel_button", $chat_id), 'callback_data' => 'skip']]
            ]
        ]);
        $telegram->editMessageText([
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => $message,
            'reply_markup' => $keyboard
        ]);
    }

    private function getKey() {
        $sql = "SELECT tg_key FROM channel_tg LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($tg_key);
            $stmt->fetch();
            $stmt->close();
            return $tg_key;
        }
        $stmt->close();
        return null;
    }
}

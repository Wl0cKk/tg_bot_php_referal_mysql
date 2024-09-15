<?php

trait Broadcast {
    
    public function initiateBroadcast() {

    }

    public function displayPosts($telegram, $chat_id) {
        $message = "Ваши посты:\n" . $channelList;
        $keyboard = [
            'inline_keyboard' => [
                [['text' => 'Начать Рассылку', 'callback_data' => 'init_cast']],
                [['text' => 'Создать пост', 'callback_data' => 'create_post']]
            ]
        ];
        $content = [
            'chat_id' => $chat_id,
            'text' => $message,
            'reply_markup' => json_encode($keyboard)
        ];
        $telegram->sendMessage($content);
    }
    
    public function handlePostName($telegram, $chat_id) {
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => 'Дайте имя вашему посту: '
        ]);
        $this->setInputMode($chat_id, 'post_name');
    }

    public function handleNewPost($telegram, $chat_id) {
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => 'Отправьте мне весь пост одним сообщением:'
        ]);
        $this->setInputMode($chat_id, 'post_set');
    }

    public function uploadNameforPost($telegram, $chat_id, $postName) {
        $stmt = $this->conn->prepare("SELECT MAX(id) as max_id FROM broadcast_posts");
        $stmt->execute();
        $result = $stmt->get_result();
        $maxId = $result->fetch_assoc()['max_id'];
        $newId = $maxId ? $maxId + 1 : 1;
        $stmt = $this->conn->prepare("INSERT INTO broadcast_posts (id, post_name) VALUES (?, ?)");
        $stmt->bind_param("is", $newId, $postName);
        if ($stmt->execute()) {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => "Название поста '$postName' успешно добавлено."
            ]);
        } else {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => "Ошибка при добавлении названия поста."
            ]);
        }
    }

    public function uploadPostDetails($telegram, $chat_id, $data) {
        $stmt = $this->conn->prepare("
            SELECT post_name FROM broadcast_posts 
            WHERE photo_id = '' AND video_id = '' AND audio_id = '' AND message_text = '' 
            LIMIT 1
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $postName = $row['post_name'];
        $photoId = '';
        $videoId = '';
        $audioId = '';
        $messageText = '';
        if (!empty($data['message']['text'])) {
            $messageText = $data['message']['text'];
        } elseif (!empty($data['message']['photo'])) {
            $photoId = $data['message']['photo'][0]['file_id'];
            $messageText = !empty($data['message']['caption']) ? $data['message']['caption'] : '';
        } elseif (!empty($data['message']['video'])) {
            $videoId = $data['message']['video']['file_id'];
            $messageText = !empty($data['message']['caption']) ? $data['message']['caption'] : '';
        } elseif (!empty($data['message']['audio'])) {
            $audioId = $data['message']['audio']['file_id'];
            $messageText = !empty($data['message']['caption']) ? $data['message']['caption'] : '';
        }
        $updateStmt = $this->conn->prepare("
            UPDATE broadcast_posts 
            SET photo_id = ?, video_id = ?, audio_id = ?, message_text = ? 
            WHERE post_name = ?
        ");
        $updateStmt->bind_param("sssss", $photoId, $videoId, $audioId, $messageText, $postName);
        if ($updateStmt->execute()) {
            if ($updateStmt->affected_rows > 0) {
                $telegram->sendMessage([
                    'chat_id' => $chat_id,
                    'text' => "Пост'$postName' успешно добавлен."
                ]);
            } else {
                $telegram->sendMessage([
                    'chat_id' => $chat_id,
                    'text' => "Запись '$postName' не обновлена."
                ]);
            }
        } else {
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => "Ошибка при обновлении поста '$postName'."
            ]);
        }
    }
    
}
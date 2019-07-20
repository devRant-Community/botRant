<?php

require_once './config.php';
require_once 'core/Bot.php';


class PingBot extends Bot {

	const DEBUG = true;

	protected function beforeExecute () {
		$this->log('Before execute.');
	}

	protected function handleNotification ($notification) {
		if ($notification['type'] !== 'comment_mention')
			return false;

		$this->log('Handling a mention notif...');

		$comment = $this->devRant->getComment($notification['comment_id']);

		preg_match('/.*?@devNews(.*)/si', $comment['body'], $matches);

		if (trim($matches[1]) === 'ping') {
			$this->log('Replying to ping...');
			$this->devRant->postComment($comment['rant_id'], 'Pong!');
		} else {
			$this->log('Invalid Comment.');
		}

		return true;
	}

	protected function afterExecute () {
		$this->log('After execute.');
	}
}


$bot = new PingBot(DEVRANT_USERNAME, DEVRANT_PASSWORD);
$bot->execute();
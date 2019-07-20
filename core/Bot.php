<?php

require_once './core/devRant.php';
require_once './core/Store.php';


class Bot {

	const DEBUG = false;

	protected $store;

	protected $devRant;

	public function log ($msg) {
		if (static::DEBUG) echo "Bot > $msg" . PHP_EOL;
	}

	public function __construct ($usernameOrEmail, $password) {
		$this->store = new Store('./store', ['prettify' => static::DEBUG, 'log' => static::DEBUG]);
		$this->devRant = new devRant();

		$authToken = $this->store->in('auth-token');

		try {
			$this->devRant->setAuthToken($authToken);
			$this->log('Auth token is valid.');
		} catch (InvalidAuthTokenException $exception) {
			$this->log('Auth token is invalid or unset!');

			try {
				$this->log('Logging in...');

				$authToken->data = $this->devRant->login($usernameOrEmail, $password);
				$this->devRant->setAuthToken($authToken->data);
			} catch (ApiException | CurlException | InvalidAuthTokenException $exception) {
				$this->log($exception->getMessage());
				die();
			}
		}
	}

	public function execute () {
		$this->beforeExecute();

		try {
			$notifications = $this->devRant->getNotifications();
			$this->devRant->clearNotifications();
		} catch (ApiException | CurlException $exception) {
			$this->log($exception->getMessage());
			return;
		}

		usort($notifications['items'], function ($a, $b) {
			return $a['created_time'] <=> $b['created_time'];
		});

		if (!isset($this->store->in('bot')['lastNotifTime']))
			$this->store->in('bot')['lastNotifTime'] = time();

		$lastNotifTime = $this->store->in('bot')['lastNotifTime'];
		$newLastNotifTime = 0;

		$didSomething = false;
		foreach ($notifications['items'] as $notification) {
			if ($notification['created_time'] > $lastNotifTime || $notification['read'] === 0) {
				try {
					$success = $this->handleNotification($notification);

					if ($success) {
						$didSomething = true;
						$newLastNotifTime = $notification['created_time'];
					}
				} catch (ApiException | CurlException $exception) {
					$this->log($exception);
				}
			}
		}

		if ($didSomething) {
			$this->store->in('bot')['lastNotifTime'] = $newLastNotifTime;
		} else {
			$this->log('Nothing to do...');
		}

		$this->afterExecute();
	}

	protected function handleNotification ($notif) {
		return false;
	}

	protected function beforeExecute () {
	}

	protected function afterExecute () {
	}
}
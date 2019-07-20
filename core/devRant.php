<?php


class CurlException extends Exception {
}


class ApiException extends Exception {
}


class InvalidAuthTokenException extends Exception {
}


class devRant {

	const DEVRANT_API = 'https://devrant.com/api';

	private $authToken = [];

	private function curl ($method, $url, $parameters = []) {
		$options = [
			CURLOPT_VERBOSE        => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_USERAGENT      => 'phpRant/1.0',
			CURLOPT_SSLVERSION     => 4,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false,
		];

		switch ($method) {
			case 'GET':
				$url .= '?' . http_build_query($parameters);
				break;

			case 'POST':
				$options += [
					CURLOPT_POST       => true,
					CURLOPT_POSTFIELDS => http_build_query($parameters),
				];
				break;

			default:
				$url .= '?' . http_build_query($parameters);
				$options += [
					CURLOPT_CUSTOMREQUEST => $method,
				];
				break;
		}

		$curl = curl_init($url);
		curl_setopt_array($curl, $options);

		$response = curl_exec($curl);

		if (curl_errno($curl))
			throw new CurlException(curl_error($curl), curl_errno($curl));

		curl_close($curl);

		return json_decode($response, true);
	}



	/* AUTH TOKEN */

	public function login ($username, $password) {
		$url = self::DEVRANT_API . '/users/auth-token';

		$parameters = [
			'app'  => 3,
			'plat' => 3,

			'username' => $username,
			'password' => $password,
		];

		$response = $this->curl('POST', $url, $parameters);

		if ($response['success'])
			return $response['auth_token'];

		throw new ApiException($response['error']);
	}

	public function getAuthToken () {
		return $this->authToken;
	}

	public function setAuthToken ($newAuthToken) {
		if (!isset($newAuthToken['user_id']) || !isset($newAuthToken['key']) || !isset($newAuthToken['id']))
			throw new InvalidAuthTokenException('One or more values are not set in the auth token!');

		if (isset($newAuthToken['expire_time']) && time() > $newAuthToken['expire_time'])
			throw new InvalidAuthTokenException('Auth token expired!');

		$this->authToken = $newAuthToken;
	}

	private function addAuthTokenToParameters (&$parameters, $errorIfEmpty = false) {
		if (empty($this->authToken)) {
			if ($errorIfEmpty)
				throw new ApiException('Auth token required!');

			return;
		}

		$parameters['token_id'] = $this->authToken['id'];
		$parameters['token_key'] = $this->authToken['key'];
		$parameters['user_id'] = $this->authToken['user_id'];
	}


	/* PROFILE */

	public function getProfile ($userID, $content = 'all', $skip = 0) {
		$url = self::DEVRANT_API . "/users/$userID";

		$parameters = [
			'app'  => 3,
			'plat' => 2,

			'content' => $content,
			'skip'    => $skip,
		];

		$this->addAuthTokenToParameters($parameters);

		$response = $this->curl('GET', $url, $parameters);

		if ($response['success'])
			return $response['profile'];

		throw new ApiException($response['error']);
	}



	/* RANT FEED */

	public function getRants ($sort = 'top', $range = 'day', $limit = 20, $skip = 0, $previousSet = 0) {
		$url = self::DEVRANT_API . "/devrant/rants";

		$parameters = [
			'app' => 3,

			'sort'     => $sort,
			'range'    => $range,
			'limit'    => $limit,
			'skip'     => $skip,
			'prev_set' => $previousSet,
		];

		$this->addAuthTokenToParameters($parameters);

		$response = $this->curl('GET', $url, $parameters);

		if ($response['success'])
			return $response;

		throw new ApiException($response['error']);
	}

	public function getCollabs ($sort = 'recent', $limit = 20, $skip = 0) {
		$url = self::DEVRANT_API . "/devrant/collabs";

		$parameters = [
			'app' => 3,

			'sort'  => $sort,
			'limit' => $limit,
			'skip'  => $skip,
		];

		$this->addAuthTokenToParameters($parameters);

		$response = $this->curl('GET', $url, $parameters);

		if ($response['success'])
			return $response['rants'];

		throw new ApiException($response['error']);
	}

	public function getStories ($sort = 'recent', $range = 'week', $limit = 20, $skip = 0) {
		$url = self::DEVRANT_API . "/devrant/story-rants";

		$parameters = [
			'app' => 3,

			'sort'  => $sort,
			'range' => $range,
			'limit' => $limit,
			'skip'  => $skip,
		];

		$this->addAuthTokenToParameters($parameters);

		$response = $this->curl('GET', $url, $parameters);

		if ($response['success'])
			return $response['rants'];

		throw new ApiException($response['error']);
	}

	public function getWeeklyRants ($week, $sort = 'recent', $limit = 20, $skip = 0) {
		$url = self::DEVRANT_API . "/devrant/weekly-rants";

		$parameters = [
			'app' => 3,

			'week'  => $week,
			'sort'  => $sort,
			'limit' => $limit,
			'skip'  => $skip,
		];

		$this->addAuthTokenToParameters($parameters);

		$response = $this->curl('GET', $url, $parameters);

		if ($response['success'])
			return $response;

		throw new ApiException($response['error']);
	}



	/* SEARCH */

	public function getSearchResults ($term) {
		$url = self::DEVRANT_API . "/devrant/search";

		$parameters = [
			'app' => 3,

			'term' => $term,
		];

		$response = $this->curl('GET', $url, $parameters);

		if ($response['success'])
			return $response['results'];

		throw new ApiException($response['error']);
	}

	public function getTopTags () {
		$url = self::DEVRANT_API . "/devrant/search/tags";

		$parameters = [
			'app'  => 3,
			'plat' => 3,
		];

		$response = $this->curl('GET', $url, $parameters);

		if ($response['success'])
			return $response['tags'];

		throw new ApiException($response['error']);
	}



	/* RANT */

	public function getRant ($rantID) {
		$url = self::DEVRANT_API . "/devrant/rants/$rantID";

		$parameters = [
			'app' => 3,
		];

		$this->addAuthTokenToParameters($parameters);

		$response = $this->curl('GET', $url, $parameters);

		if ($response['success'])
			return $response;

		throw new ApiException($response['error']);
	}

	public function postRant ($text, $tags = [], $type = 1, $imagePath = false) {
		$url = self::DEVRANT_API . "/devrant/rants";

		$parameters = [
			'app'  => 3,
			'plat' => 3,

			'rant' => $text,
			'tags' => implode(', ', $tags),
			'type' => $type,
		];

		if ($imagePath && file_exists($imagePath) && is_readable($imagePath)) {
			$mimeTypes = [
				'jpg' => 'image/jpg',
				'png' => 'image/png',
				'gif' => 'image/gif',
			];

			$fileExtension = pathinfo($imagePath, PATHINFO_EXTENSION);

			$mimeType = $mimeTypes[$fileExtension];
			$parameters['image'] = curl_file_create($imagePath, $mimeType, "rant_image.$fileExtension");
		}

		$this->addAuthTokenToParameters($parameters, true);

		$response = $this->curl('POST', $url, $parameters);

		if ($response['success'])
			return $response['rant_id'];

		throw new ApiException($response['error']);
	}

	public function editRant ($rantID, $text, $tags = [], $imagePath = false) {
		$url = self::DEVRANT_API . "/devrant/rants/$rantID";

		$parameters = [
			'app'  => 3,
			'plat' => 2,

			'rant' => $text,
			'tags' => implode(', ', $tags),
		];

		if ($imagePath && file_exists($imagePath) && is_readable($imagePath)) {
			$mimeTypes = [
				'jpg' => 'image/jpg',
				'png' => 'image/png',
				'gif' => 'image/gif',
			];

			$fileExtension = pathinfo($imagePath, PATHINFO_EXTENSION);

			$mimeType = $mimeTypes[$fileExtension];
			$parameters['image'] = curl_file_create($imagePath, $mimeType, "rant_image.$fileExtension");
		}

		$this->addAuthTokenToParameters($parameters, true);

		$response = $this->curl('POST', $url, $parameters);

		if ($response['success'])
			return $response;

		throw new ApiException($response['error']);
	}

	public function deleteRant ($rantID) {
		$url = self::DEVRANT_API . "/devrant/rants/$rantID";

		$parameters = [
			'app'  => 3,
			'plat' => 2,
		];

		$this->addAuthTokenToParameters($parameters, true);

		$response = $this->curl('DELETE', $url, $parameters);

		if ($response['success'])
			return $response;

		throw new ApiException($response['error']);
	}



	/* COMMENT */

	public function getComment ($commentID) {
		$url = self::DEVRANT_API . "/comments/$commentID";

		$parameters = [
			'app'  => 3,
			'plat' => 2,
		];

		$this->addAuthTokenToParameters($parameters);

		$response = $this->curl('GET', $url, $parameters);

		if ($response['success'])
			return $response['comment'];

		throw new ApiException($response['error']);
	}

	public function postComment ($rantID, $text, $imagePath = false) {
		$url = self::DEVRANT_API . "/devrant/rants/$rantID/comments";

		$parameters = [
			'app'  => 3,
			'plat' => 2,

			'comment' => $text,
		];

		if ($imagePath && file_exists($imagePath) && is_readable($imagePath)) {
			$mimeTypes = [
				'jpg' => 'image/jpg',
				'png' => 'image/png',
				'gif' => 'image/gif',
			];

			$fileExtension = pathinfo($imagePath, PATHINFO_EXTENSION);

			$mimeType = $mimeTypes[$fileExtension];
			$parameters['image'] = curl_file_create($imagePath, $mimeType, "comment_image.$fileExtension");
		}

		$this->addAuthTokenToParameters($parameters, true);

		$response = $this->curl('POST', $url, $parameters);

		if ($response['success'])
			return $response;

		throw new ApiException($response['error']);
	}

	public function editComment ($commentID, $text, $imagePath) {
		$url = self::DEVRANT_API . "/comments/$commentID";

		$parameters = [
			'app'  => 3,
			'plat' => 2,

			'comment' => $text,
		];

		if ($imagePath && file_exists($imagePath) && is_readable($imagePath)) {
			$mimeTypes = [
				'jpg' => 'image/jpg',
				'png' => 'image/png',
				'gif' => 'image/gif',
			];

			$fileExtension = pathinfo($imagePath, PATHINFO_EXTENSION);

			$mimeType = $mimeTypes[$fileExtension];
			$parameters['image'] = curl_file_create($imagePath, $mimeType, "comment_image.$fileExtension");
		}

		$this->addAuthTokenToParameters($parameters, true);

		$response = $this->curl('POST', $url, $parameters);

		if ($response['success'])
			return $response;

		throw new ApiException($response['error']);
	}

	public function deleteComment ($commentID) {
		$url = self::DEVRANT_API . "/comments/$commentID";

		$parameters = [
			'app'  => 3,
			'plat' => 2,
		];

		$this->addAuthTokenToParameters($parameters, true);

		$response = $this->curl('DELETE', $url, $parameters);

		if ($response['success'])
			return $response;

		throw new ApiException($response['error']);
	}



	/* FAVORITE */

	private function setFavorite ($status, $rantID) {
		$url = self::DEVRANT_API . "/devrant/rants/$rantID/" . ($status === true ? 'favorite' : 'unfavorite');

		$parameters = [
			'app'  => 3,
			'plat' => 2,
		];

		$this->addAuthTokenToParameters($parameters, true);

		$response = $this->curl('POST', $url, $parameters);

		if ($response['success'])
			return $response;

		throw new ApiException($response['error']);
	}

	public function favoriteRant ($rantID) {
		return $this->setFavorite(true, $rantID);
	}

	public function unfavoriteRant ($rantID) {
		return $this->setFavorite(false, $rantID);
	}



	/* SUBSCRIBE */

	private function setSubscribe ($status, $userID) {
		$url = self::DEVRANT_API . "/users/$userID/subscribe";

		$parameters = [
			'app'  => 3,
			'plat' => 2,
		];

		$this->addAuthTokenToParameters($parameters, true);

		$response = $this->curl($status === true ? 'POST' : 'DELETE', $url, $parameters);

		if ($response['success'])
			return $response;

		throw new ApiException($response['error']);
	}

	public function subscribeUser ($userID) {
		return $this->setSubscribe(true, $userID);
	}

	public function unsubscribeUser ($userID) {
		return $this->setSubscribe(false, $userID);
	}



	/* VOTING RANT */

	private function setRantVoteState ($state, $rantID, $reason = 0) {
		$url = self::DEVRANT_API . "/devrant/rants/$rantID/vote";

		$parameters = [
			'app'  => 3,
			'plat' => 3,

			'vote' => $state,
		];

		if ($state === -1)
			$parameters['reason'] = $reason;

		$this->addAuthTokenToParameters($parameters, true);

		$response = $this->curl('POST', $url, $parameters);

		if ($response['success'])
			return $response['rant'];

		throw new ApiException($response['error']);
	}

	public function upvoteRant ($rantID) {
		return $this->setRantVoteState(1, $rantID);
	}

	public function downvoteRant ($rantID, $reason = 0) {
		return $this->setRantVoteState(-1, $rantID, $reason);
	}

	public function unvoteRant ($rantID) {
		return $this->setRantVoteState(0, $rantID);
	}



	/* VOTING COMMENT */

	private function setCommentVoteState ($state, $commentID, $reason = 0) {
		$url = self::DEVRANT_API . "/comments/$commentID/vote";

		$parameters = [
			'app'  => 3,
			'plat' => 3,

			'vote' => $state,
		];

		if ($state === -1)
			$parameters['reason'] = $reason;

		$this->addAuthTokenToParameters($parameters, true);

		$response = $this->curl('POST', $url, $parameters);

		if ($response['success'])
			return $response['comment'];

		throw new ApiException($response['error']);
	}

	public function upvoteComment ($commentID) {
		return $this->setCommentVoteState(1, $commentID);
	}

	public function downvoteComment ($commentID, $reason = 0) {
		return $this->setCommentVoteState(-1, $commentID, $reason);
	}

	public function unvoteComment ($commentID) {
		return $this->setCommentVoteState(0, $commentID);
	}



	/* NOTIFICATIONS */

	public function getNotifications ($lastTime = 0) {
		$url = self::DEVRANT_API . "/users/me/notif-feed";

		$parameters = [
			'app'  => 3,
			'plat' => 2,

			'last_time' => $lastTime,
		];

		$this->addAuthTokenToParameters($parameters, true);

		$response = $this->curl('GET', $url, $parameters);

		if ($response['success'])
			return $response['data'];

		throw new ApiException($response['error']);
	}

	public function clearNotifications () {
		$url = self::DEVRANT_API . "/users/me/notif-feed";

		$parameters = [
			'app'  => 3,
			'plat' => 2,
		];

		$this->addAuthTokenToParameters($parameters, true);

		$response = $this->curl('DELETE', $url, $parameters);

		if ($response['success'])
			return $response;

		throw new ApiException($response['error']);
	}



	/* MISC */

	public function getWeeklyTopics () {
		$url = self::DEVRANT_API . "/devrant/weekly-list";

		$parameters = [
			'app'  => 3,
			'plat' => 2,
		];

		$this->addAuthTokenToParameters($parameters);

		$response = $this->curl('GET', $url, $parameters);

		if ($response['success'])
			return $response['weeks'];

		throw new ApiException($response['error']);
	}

	public function getSubscribers () {
		$url = self::DEVRANT_API . "/devrant/supporters";

		$parameters = [
			'app'  => 3,
			'plat' => 2,
		];

		$this->addAuthTokenToParameters($parameters);

		$response = $this->curl('GET', $url, $parameters);

		if ($response['success'])
			return $response['items'];

		throw new ApiException($response['error']);
	}

	public function getSurpriseRant () {
		$url = self::DEVRANT_API . "/devrant/rants/surprise";

		$parameters = [
			'app'  => 3,
			'plat' => 2,
		];

		$this->addAuthTokenToParameters($parameters);

		$response = $this->curl('GET', $url, $parameters);

		if ($response['success'])
			return $response;

		throw new ApiException($response['error']);
	}
}
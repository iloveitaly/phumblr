<?php

class Tumblr_Core {	 
	function __construct($username) {
		$this->username = $username;
		$this->cache = Cache::instance();
	}
	
	function read($args=array()) {
		$json = $this->_api('read', $args);
		$posts = array();
		foreach ($json->posts as $post) {
			$posts[] = new Tumblr_Post($post);
		}
		$this->name = $json->tumblelog->name;
		$this->title = $json->tumblelog->title;
		$this->description = $json->tumblelog->description;
		$this->timezone = $json->tumblelog->timezone;
		$this->cname = $json->tumblelog->cname;
		$this->feeds = $json->tumblelog->feeds;
		$this->posts = $posts;
		$k = 'posts-start'; $this->posts_start = $json->$k;
		$k = 'posts-total'; $this->posts_total = $json->$k;
		$k = 'posts-type'; $this->posts_type = $json->$k;
		return $posts;
	}
	
	function _api($method, $args) {
		$url = 'http://'.$this->username.'.tumblr.com/api/'.$method.'/json?' . http_build_query($args);
		return Tumblr_HTTP::get_cached($url);
	}
	
	public function process($action) {
		$tumblr = new Tumblr(Kohana::config('tumblr.username'));
		
		switch($action) {
			case 'post':
				$tumblr->read(array('id'=>$_GET['post_id']));
				include 'php/_posts.php';
		   		break;
			case 'search':
				$tumblr->read(array('search'=>$_GET['q']));
				include 'php/_search.php';			
			default:
				$tumblr->read();
				include 'php/_posts.php';
		}
	}
}

class Tumblr_Post {
	const TYPE_REGULAR = 'regular';
	const TYPE_PHOTO = 'photo';
	const TYPE_QUOTE = 'quote';
	const TYPE_LINK = 'link';
	const TYPE_CONVERSATION = 'conversation';
	const TYPE_VIDEO = 'video';
	const TYPE_AUDIO = 'audio';
	
	function __construct($data) {
		$vars = get_object_vars($data);
		$this->id = (int)$vars['id'];
		$this->url = $vars['url'];
		$this->url_with_slug = $vars['url-with-slug'];
		$this->slug = substr($this->url_with_slug, strlen($this->url)+1);
		$this->type = $vars['type'];
		$this->date_gmt = strtotime($vars['date-gmt']);
		$this->date = strtotime($vars['date']);
		$this->bookmarklet = (int)$vars['bookmarklet'];
		$this->mobile = (int)$vars['mobile'];
		$this->feed_item = $vars['feed-item'];
		$this->from_feed_id = (int)$vars['from-feed-id'];
		$this->unix_timestamp = (int)$vars['unix-timestamp'];
		$this->format = $vars['format'];
		$this->tags = isset($vars['tags']) ? $vars['tags'] : array();
		switch ($this->type) {
		case self::TYPE_REGULAR:
			$this->regular_title = $vars['regular-title'];
			$this->regular_body = $vars['regular-body'];
			break;
		case self::TYPE_PHOTO:
			$this->photo_caption = $vars['photo-caption'];
			$this->photo_urls = array();
			foreach ($vars as $key=>$value) {
				if (preg_match('/^photo-url-([0-9]+)$/', $key, $matches)) {
					$this->photo_urls[(int)$matches[1]] = $value;
				}
			}
			$this->photo_url = $this->photo_urls[max(array_keys($this->photo_urls))];
			$this->photos = isset($vars['photos']) ? $vars['photos'] : array();
			break;
		case self::TYPE_QUOTE:
			$this->quote_text = $vars['quote-text'];
			$this->quote_source = $vars['quote-source'];
			break;
		case self::TYPE_LINK:
			$this->link_text = $vars['link-text'];
			$this->link_url = $vars['link-url'];
			$this->link_description = $vars['link-description'];
			break;
		case self::TYPE_CONVERSATION:
			$this->conversation_title = $vars['conversation-title'];
			$this->conversation_text = $vars['conversation-text'];
			$this->conversation_lines = array();
			foreach ($vars['conversation'] as $line) {
				$this->conversation_lines[] = array(
					'name' => $line->name,
					'label' => $line->label,
					'phrase' => $line->phrase);
			}
			break;
		case self::TYPE_VIDEO:
			$this->video_caption = $vars['video-caption'];
			$this->video_source = $vars['video-source'];
			$this->video_player = $vars['video-player'];
			break;
		case self::TYPE_AUDIO:
			$this->audio_caption = $vars['audio-caption'];
			$this->audio_player = $vars['audio-player'];
			$this->audio_plays = isset($vars['audio-plays']) ? (int)$vars['audio-plays'] : 0;
			break;
		}
	}
	
	function permalink() {
		global Kohana::config('tumblr.url_prefix');
		return Kohana::config('tumblr.url_prefix').'/post/'.$this->id.'/'.$this->slug;
	}
}

class Tumblr_HTTP {
	static $cache_time = 300; // 5 minutes
	static $_cache_pending = array();
	
	static function get($url) {
		if (function_exists('curl_init')) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			$json = curl_exec($ch);
			curl_close($ch);
		}
		else {
			$json = file_get_contents($url);
		}
		if (preg_match('/^.+?(\{.+\});$/m', $json, $matches)) {
			$json = $matches[1];
		}
		
		$this->cache->set('tumblr.'.md5($url), $json, NULL, self::$cache_time);
		return $json;
	}
	
	static function get_cached($url) {
		$cacheData = $this->cache->get('tumblr.'.md5($url));
		if(!empty($cacheData)) $json = json_decode($cacheData);
		else $json = json_decode(self::get($url, $fn));

		return $json;
	}
}

function tumblr_http_shutdown() {
	// Tumblr_HTTP::_cache_pending();
}
register_shutdown_function('tumblr_http_shutdown');

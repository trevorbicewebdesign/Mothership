<?php
class JConfig {
	public $offline = false;
	public $offline_message = 'This site is down for maintenance.<br>Please check back again soon.';
	public $display_offline_message = 1;
	public $offline_image = '';
	public $sitename = 'Mothership Test Site';
	public $editor = 'tinymce';
	public $captcha = '0';
	public $list_limit = 20;
	public $access = 1;
	public $debug = true;
	public $debug_lang = false;
	public $debug_lang_const = true;
	public $dbtype = 'mysqli';
	public $host = 'db'; // Docker service name
	public $user = 'root';
	public $password = 'root';
	public $db = 'local'; // Will be changed below for tests
	public $dbprefix = 'jos_';
	public $dbencryption = 0;
	public $dbsslverifyservercert = false;
	public $dbsslkey = '';
	public $dbsslcert = '';
	public $dbsslca = '';
	public $dbsslcipher = '';
	public $force_ssl = 0;
	public $live_site = '';
	public $secret = 'RANDOM_SECRET'; // Can be anything random
	public $gzip = false;
	public $error_reporting = 'none';
	public $helpurl = 'https://help.joomla.org/proxy?keyref=Help{major}{minor}:{keyref}&lang={langcode}';
	public $offset = 'UTC';
	public $mailonline = true;
	public $mailer = 'mail';
	public $mailfrom = 'admin@example.com';
	public $fromname = 'Mothership CI';
	public $sendmail = '/usr/sbin/sendmail';
	public $smtpauth = false;
	public $smtpuser = '';
	public $smtppass = '';
	public $smtphost = 'localhost';
	public $smtpsecure = 'none';
	public $smtpport = 25;
	public $caching = 0;
	public $cache_handler = 'file';
	public $cachetime = 15;
	public $cache_platformprefix = false;
	public $MetaDesc = '';
	public $MetaAuthor = true;
	public $MetaVersion = false;
	public $robots = '';
	public $sef = true;
	public $sef_rewrite = false;
	public $sef_suffix = false;
	public $unicodeslugs = false;
	public $feed_limit = 10;
	public $feed_email = 'none';
	public $log_path = '/var/www/html/logs';
	public $tmp_path = '/var/www/html/tmp';
	public $lifetime = 15;
	public $session_handler = 'database';
	public $shared_session = false;
	public $session_metadata = true;

	public function __construct()
	{
		$HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'] ?? '';

		if (preg_match('#HeadlessChrome#', $HTTP_USER_AGENT)) {
			$HTTP_USER_AGENT = 'HeadlessChrome';
		}

		switch ($HTTP_USER_AGENT) {
			case 'Symfony BrowserKit':
			case 'HeadlessChrome':
				$this->debug = 0;
				$this->db = 'test';
				break;
		}

		if (getenv('APPLICATION_ENV') === 'test') {
			$this->debug = 0;
			$this->db = 'test';
		}
	}
}

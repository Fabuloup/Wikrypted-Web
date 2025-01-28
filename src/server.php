<?php
error_reporting(0);
ini_set('default_socket_timeout', 1);

$articlesFile = 'articles.json';

class Krypter {
    private static $instance;
    private $key;

    private function __construct() {
        $this->setKey();
    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Krypter();
        }
        return self::$instance;
    }

    public function setKey() {
        $this->key = rand(0,999999);
    }

    public function encode($text) {
        if($this->key === null) {
            $this->setKey();
        }
        $encodedChars = [];
        foreach (str_split($text) as $c) {
            if ($c == "\n") {
                $encodedChars[] = $c;
            } else {
                $encodedChars[] = chr(ord($c) + $this->PerlinNoise($this->key + ord($c)) * 3);
            }
        }
        $text = implode('', $encodedChars);
        return $text;
    }

    // Perlin Noise implementation
    private function PerlinNoise($x){
        $x = ($x << 13) ^ $x;
        return (1.0 - (($x * ($x * $x * 15731 + 789221) + 1376312589) & 0x7fffffff) / 1073741824.0);
    }
}

function getRandomArticleUrl() {
    $url = 'https://en.wikipedia.org/wiki/Special:Random';
    $content = file_get_contents($url);
    preg_match('/<link rel="canonical" href="(.*?)"/', $content, $matches);
    return $matches[1];
}

function fetchWikipediaArticle($url) {
    global $articlesFile;
    $apiUrl = 'https://en.wikipedia.org/api/rest_v1/page/summary/' . basename(parse_url($url, PHP_URL_PATH));
    $content = file_get_contents($apiUrl);
    $articles = [];

    if($content === false && file_exists($articlesFile)) {
        $articles = json_decode(file_get_contents($articlesFile), true);
        $key = array_rand($articles);
        return $articles[$key];
    }

    $json = json_decode($content, true);

    $article = [
        'url' => $url,
        'title' => $json['title'] ?? '',
        'description' => $json['extract'] ?? ''
    ];

    $articles[$url] = $article;
    file_put_contents($articlesFile, json_encode($articles, JSON_PRETTY_PRINT));

    return $article;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $url = getRandomArticleUrl();

    $articles = [];

    $article = [];

    if (file_exists($articlesFile)) {
        $articles = json_decode(file_get_contents($articlesFile), true);
    }

    if (isset($articles[$url])) {
        $article = $articles[$url];
    } else {
        $article = fetchWikipediaArticle($url);
    }

    $krypter = Krypter::getInstance();
    $krypter->setKey();
    $encodedArticle = [
        'url' => $article['url'],
        'title' => $krypter->encode($article['title']),
        'description' => $krypter->encode($article['description'])
    ];

    $message = [
        'original' => $article,
        'encoded' => $encodedArticle
    ];

    echo json_encode($message);
}
?>
<?php
/*
=====================================================
 Author : Mehmet Hanoğlu <dle.net.tr>
-----------------------------------------------------
 License : MIT License
-----------------------------------------------------
 Copyright (c)
-----------------------------------------------------
 Date : 28.09.2018 [2.5]
=====================================================
*/

if ( ! defined( 'E_DEPRECATED' ) ) {
	@error_reporting( E_ALL ^ E_WARNING ^ E_NOTICE );
	@ini_set( 'error_reporting', E_ALL ^ E_WARNING ^ E_NOTICE );
} else {
	@error_reporting( E_ALL ^ E_WARNING ^ E_DEPRECATED ^ E_NOTICE );
	@ini_set( 'error_reporting', E_ALL ^ E_WARNING ^ E_DEPRECATED ^ E_NOTICE );
}

class FilmReader {

	public $config = [
		'location' 				=> "tr-TR", // Portugal: (pt-PT, pt), English-USA: (en-US), English-UK: (en-GB), Turkey: (tr-TR, tr), France (fr-FR, fr), Germany (de-DE), Russia: (ru-RU),
		'actor_count' 			=> 10,
		'runtime_replace' 		=> true,
		'runtime_replace_map' 	=> ["h" => " saat", "min" => " dakika"],  // if you dont want, clear the array. Example: [];
	];

	public function get( $url ) {
		$html = $this->getURLContent( $url );

		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		$x = new DOMXPath( $dom );

		foreach($x->query("//meta") as $node) {
			$_tmp['meta'][$node->getAttribute("property")] = $node->getAttribute("content");
		}
		preg_match("/(.*?)\s\(([0-9]+)\)/", $_tmp['meta']['og:title'], $name, PREG_OFFSET_CAPTURE);
		$film = array(
			'img'			=> $_tmp['meta']['og:image'],
			'namelong'		=> $_tmp['meta']['og:title'],
			'name'			=> $name[1][0],
			'year'			=> $name[2][0],
			'url'			=> $_tmp['meta']['og:url'],
			'type'			=> $_tmp['meta']['og:type'],
			'crating'		=> "",
		);
		$html = str_replace( array( "\n", "\r" ), "", $html );

		preg_match("/\(([0-9]+) episodes\)/is", $html, $eps );
		$film['runtime'] = $this->cleanWords( $x->query("//time")->item(0)->nodeValue ); $_tmp = [];
		if ( $this->config['runtime_replace'] ) {
			$film['runtime'] = str_replace( array_keys( $this->config['runtime_replace_map'] ), array_values( $this->config['runtime_replace_map'] ), $film['runtime'] );
		}
		foreach($x->query('//span[@itemprop="genre"]/text()') as $node) { $_tmp[] = $this->cleanWords( $node->nodeValue ); }
		$film['genres'] = implode(", ", $_tmp);
		$film['ratinga'] = $this->cleanWords( $x->query('//span[@itemprop="ratingValue"]')->item(0)->nodeValue );
		$film['ratingb'] = $this->cleanWords( $x->query('//span[@itemprop="bestRating"]')->item(0)->nodeValue );
		$film['ratingc'] = $this->cleanWords( $x->query('//span[@itemprop="ratingCount"]')->item(0)->nodeValue );
		$_tmp = [];
		foreach($x->query('//div[@class="txt-block"]') as $node) { $_data1 = $this->cleanWords( $node->nodeValue ); $_data2 = explode(':', $_data1 ); $_tmp[ $_data2[0] ] = trim( substr($_data1, strpos($_data1, ':')+1 ) ); }
		$film['country'] = str_replace( "|", ", ", $_tmp['Country'] );
		$film['locations'] = $_tmp['Filming Locations'];
		$film['language'] = str_replace("|", ", ", $_tmp['Language'] );
		$film['productionfirm'] = $_tmp['Production Co'];
		$film['datelocal'] = trim( $_tmp['Release Date'] );
		$film['namelocal'] = $_tmp['Also Known As'];
		$film['color'] = $_tmp['Color'];
		$film['sound'] = str_replace("|", ", ", $_tmp['Sound Mix'] );
		$film['budget'] = $_tmp['Budget'];
		$film['aratio'] = $_tmp['Aspect Ratio'];
		$film['tagline'] = $_tmp['Taglines'];
		$_tmp = explode("_V1", $film['img'] );
		if ( strpos( $_tmp[0], "/imdb/images/logos/imdb_fb_logo" ) === false ) {
			$film['orgimg'] = $_tmp[0] . "_V1" . ".jpg";
		}
		$film['ratinga'] = str_replace( ".", ",", $film['ratinga'] );
		$_tmp = [];
		foreach( $x->query('//div[@class="credit_summary_item"][2]/span[@itemtype="http://schema.org/Person"]/a/span') as $node ) {
			$_tmp[] = $this->cleanWords( $node->nodeValue );
		}
		$film['writers'] = implode( ", ", $_tmp ); $_tmp = [];

		foreach( $x->query('//table[@class="cast_list"]/tr/td[2]/a') as $node ) {
			$_tmp[] = $this->cleanWords( $node->nodeValue );
		}
		$film['actors'] = implode( ", ", array_slice( $_tmp, 0, $this->config['actor_count'] ) );

		if ( $film['type'] == "video.tv_show" ) {
			$film['episodes'] = $eps[1][0]; unset( $eps );
			$film['name'] = str_replace(": The TV Show", "", $film['name']);
			$film['namelong'] = str_replace("â", "-", $film['namelong']);
			if ( empty( $film['year'] ) ) {
				$_tmp = explode("(", $film['namelong']); $_tmp = str_replace(")", "", $_tmp[1]); $_tmp = explode(" ", $_tmp);
				$film['year'] = trim( $_tmp[2] ); $_tmp = [];
			}
			if ( empty( $film['name'] ) ) { $_tmp = explode("(", $film['namelong'] ); $film['name'] = trim( $_tmp[0] ); $_tmp = []; }
			$film['years'] = [];
			$film['seasons'] = [];
			foreach($x->query('//div[@id="titleTVSeries"]/div/span/a') as $node) {
				if ( strpos( $node->getAttribute("href"), "?year=" ) != false ) {
					preg_match("/year=([0-9]{4})&/", $node->getAttribute("href"), $r);
					$film['years'][] = $r[1];
				}
				else if ( strpos( $node->getAttribute("href"), "?season=" ) != false ) {
					preg_match("/season=([0-9]+)&/", $node->getAttribute("href"), $r);
					$film['seasons'][] = $r[1];
				}
			}
			sort( $film['years'], SORT_NUMERIC);
			sort( $film['seasons'], SORT_NUMERIC);
			$film['years'] = implode(",", array_unique( $film['years'] ) );
			$film['season_count'] = count( $film['seasons'] );
			$film['seasons'] = implode(",", array_unique( $film['seasons'] ) );

		} else if ( $film['type'] == "video.movie" ) {

			$_tmp = [];
			foreach( $x->query('//div[@class="credit_summary_item"][1]/span[@itemprop="director"]/a/span') as $node ) {
				$_tmp[] = $this->cleanWords( $node->nodeValue );
			}
			$film['director'] = implode( ", ", $_tmp );

			if ( $x->query('//meta[@itemprop="contentRating"]')->item(0) ) $film['crating'] = $this->cleanWords( $x->query('//meta[@itemprop="contentRating"]')->item(0)->getAttribute('content') );
		}

		$_tmp = [];
		foreach( $x->query('//div[@id="soundtracks"]/a') as $node ) {
			$_tmp[] = $this->cleanWords( $node->nodeValue );
		}
		$film['soundtracks'] = implode( ", ", array_filter( array_unique( $_tmp ) ) );

		$re = '/<script type="application\/ld\+json">([\s\S]*?)<\/script>/s';
		if ( preg_match( $re, $html, $matches ) ) {
			$data = json_decode( $matches[1], true );
			$film['img'] = $data['image'];
			$film['orgimg'] = $data['image'];
			$film['name'] = $data['name'];
			if ( is_array( $data['genre'] ) ) {
				$film['genres'] = implode(", ", $data['genre']);
			} else {
				$film['genres'] = $data['genre'];
			}
			$film['crating'] = $data['contentRating'];

			$_tmp = [];
			foreach( $data['director'] as $key => $value ) {
				if ( is_array( $value ) ) {
					$_tmp[] = $value['name'];
				} else if ( $key == 'name' ) {
					$_tmp[] = $value;
				}
			}
			$film['director'] = implode(',', $_tmp);

			if ( empty( $film['actors'] ) ) {
				$_tmp = [];
				foreach( $data['actor'] as $act ) { $_tmp[] = $act['name']; }
				$film['actors'] = implode(", ", array_slice( $_tmp, 0, $this->config['actor_count'] ));
			}

			$_tmp = [];
			foreach( $data['creator'] as $crt ) {
				if ( $crt['@type'] == 'Person' ) $_tmp[] = $crt['name'];
			}
			$film['writers'] = implode(", ", $_tmp);

			$film['ratingc'] = $data['aggregateRating']['ratingCount'];
			$film['ratingb'] = $data['aggregateRating']['bestRating'];
			$film['ratinga'] = $data['aggregateRating']['ratingValue'];
		}
		$film['story'] = $this->cleanWords( $x->query('//div[@id="titleStoryLine"]/div/p/span/text()')->item(0)->nodeValue );

		return $film;
	}

	private function cleanWords( $text ) {
		$text = trim( str_replace( array( "\t", "  ", "\"", "\r\n", "\n", "»", "&#187;", "&raquo;", "See more" ), "", $text ) );
		return $text;
	}

	private function getURLContent($url) {
		$ch = curl_init( $url );
		$header[] = "Accept-Language:" . $this->config['location'] . ";q=0.5";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_ENCODING, "utf-8");
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
		curl_setopt($ch, CURLOPT_TIMEOUT, 120);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
		$output  = curl_exec( $ch );
		curl_close( $ch );
		return $output;
	}
}

/*
@header( "Content-type: text/html; charset=utf-8" );
$f = new FilmReader();
echo "<pre>";
print_r( $f->get("https://www.imdb.com/title/tt0133093/") );
print_r( $f->get("https://www.imdb.com/title/tt0111161/") );
print_r( $f->get("https://www.imdb.com/title/tt0455944/") );
print_r( $f->get("http://www.imdb.com/title/tt0111161/") );
print_r( $f->get("http://www.imdb.com/title/tt2820852/") );
echo "</pre>";
*/

?>
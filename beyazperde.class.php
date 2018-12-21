<?php
/*
=====================================================
 Author : Mehmet Hanoğlu <dle.net.tr>
-----------------------------------------------------
 License : MIT License
-----------------------------------------------------
 Copyright (c)
-----------------------------------------------------
 Date : 12.02.2018 [1.4]
=====================================================
*/

if ( !defined( 'E_DEPRECATED' ) ) {
	@error_reporting ( E_ALL ^ E_WARNING ^ E_NOTICE );
	@ini_set ( 'error_reporting', E_ALL ^ E_WARNING ^ E_NOTICE );
} else {
	@error_reporting ( E_ALL ^ E_WARNING ^ E_DEPRECATED ^ E_NOTICE );
	@ini_set ( 'error_reporting', E_ALL ^ E_WARNING ^ E_DEPRECATED ^ E_NOTICE );
}

class FilmReader {

	private $config = [
		'screens' 		=> true,  // Ekran görüntülerini çekme ayarı
		'screens_count' => 5,     // Çekilecek ekran görüntüsü sayısı
	];

	public function get( $url ) {
		$html = $this->getURLContent( $url );
		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		$x = new DOMXPath( $dom );
		$film = array();
		foreach($x->query("//meta") as $node) { $_tmp['meta'][$node->getAttribute("property")] = $node->getAttribute("content"); }
		$film = array(
			'namelocal'		=> $_tmp['meta']['og:title'],
			'name'			=> $_tmp['meta']['og:title'],
			'url'			=> $url,
			'orgimg'		=> $_tmp['meta']['og:image'],
		);
		$film['img'] = $this->cleanWords( $x->query('//img[@itemprop="image"]')->item(0)->getAttribute('src') );
		$film['datelocal'] = $this->cleanWords( $x->query('//span[@itemprop="datePublished"]')->item(0)->nodeValue );
		$film_directors = array();
		foreach( $x->query('//span[@itemprop="director"]/a/span[@itemprop="name"]') as $node ) {
			$film_directors[] = $this->cleanWords( $node->nodeValue );
		}
		$film['director'] = implode( ",", $film_directors );
		$_tmp = array();
		foreach( $x->query('//a[contains(@href,"/sanatcilar/")]') as $node ) { $val = $this->cleanWords( $node->nodeValue ); if ( ! in_array( $val, $film_directors ) ) $_tmp[] = $val; }
		$film['actors'] = implode( ", ", $_tmp ); $_tmp = array();
		foreach( $x->query('//span[@itemprop="genre"]') as $node ) { $_tmp[] = $this->cleanWords( $node->nodeValue ); }
		$film['genres'] = implode( ", ", $_tmp ); $_tmp = array();
		$film['ratinga'] = $this->cleanWords( $x->query('//span[@itemprop="ratingValue"]')->item(0)->nodeValue );
		if ( $x->query('//meta[@itemprop="bestRating"]')->item(0) ) {
			$film['ratingb'] = $this->cleanWords( $x->query('//meta[@itemprop="bestRating"]')->item(0)->getAttribute('content') );
		}
		$film['ratingc'] = $this->cleanWords( $x->query('//span[@itemprop="ratingCount"]')->item(0)->nodeValue );
		foreach( $x->query('//tr[contains(@class, "visible")]/th/div') as $node ) { $_tmp[ $count ] = array(); $_tmp[ $count ][] = $this->cleanWords( $node->nodeValue ); $count++; } $count = 0;
		foreach( $x->query('//tr[contains(@class, "visible")]/td[not(contains(@class,"cell_sep"))]') as $node ) { $_tmp[ $count ][] = $this->cleanWords( $node->nodeValue ); $count++; }
		foreach( $_tmp as $d ) {
			if ( $d[0] == "Orijinal adı" ) $film['name'] = $d[1];
			else if ( $d[0] == "Yapım yılı" ) $film['year'] = $d[1];
			else if ( $d[0] == "Bütçe" ) $film['budget'] = $d[1];
		}

		$html = str_replace( "\r", "", $html );
		$html = str_replace( "\n", "", $html );
		$html = str_replace( "  ", "", $html );
		$html = str_replace( "><", ">|<", $html );
		$html = strip_tags( $html );
		$html = str_replace( "||", "|", $html );

		if ( preg_match( "#\|Ülke\|(.+?)\|#is", $html, $m ) ) {
			$film['country'] = $this->cleanWords( $m[1] );
		}

		if ( preg_match( "#\|Özet ve Detaylar\|(.+?)\|#is", $html, $m ) ) {
			$film['story'] = str_replace( "&nbsp;", " ", ( $this->cleanWords( $m[1] ) ) );
		}

		if ( preg_match( "#\|Yönetmen\|\|(.+?)\|#is", $html, $m ) ) {
			$film['director'] = $this->cleanWords( $m[1] );
		}

		if ( preg_match( "#\|Yapım yılı\|(.+?)\|#is", $html, $m ) ) {
			$film['year'] = $this->cleanWords( $m[1] );
		}

		if ( preg_match( "#\|Dağıtımcı\|(.+?)\|#is", $html, $m ) ) {
			$film['productionfirm'] = $this->cleanWords( $m[1] );
		}

		if ( preg_match( "#\|Vizyon tarihi\|(.+?)\|#is", $html, $m ) ) {
			$film['datelocal'] = $this->cleanWords( $m[1] );
			$_tmp = explode( "(", $film['datelocal'] );
			$film['datelocal'] = $this->cleanWords( $_tmp[0] );
			$film['runtime'] = $this->cleanWords( trim( $_tmp[1], ")" ) );
			$film['year'] = substr( $film['datelocal'], -4 );
		}

		$film['screens'] = [];
		if ( $this->config['screens'] ) {
			$fotolar_url = trim( $url, '/' ) . "/fotolar/";
			$html = $this->getURLContent( $fotolar_url );
			$dom = new DOMDocument();
			@$dom->loadHTML( $html );
			$x = new DOMXPath( $dom );
			foreach( $x->query('//img[@class="shot-img"]') as $node ) {
				$src = $node->getAttribute('data-src');
				preg_match( "#\/[a-z]_([0-9]+)_([0-9]+)\/#i", $src, $size );
				$film['screens'][] = str_replace( $size[0], "/r_1280_720/", $src );
			}
			$film['screens'] = array_slice( $film['screens'], 0, $this->config['screens_count'] );
		}

		unset( $_tmp );
		return $film;
	}

	private function cleanWords( $text ) {
		$text = str_replace( array( "\t", "  ", "\r\n", "\n" ), "", $text );
		return trim( $text );
	}

	private function getURLContent($url) {
		if ( function_exists('curl_exec') ) {
			$ch = curl_init( $url );
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_ENCODING, "");
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
			curl_setopt($ch, CURLOPT_TIMEOUT, 120);
			curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
			$output  = curl_exec( $ch );
			curl_close( $ch );
		} else {
			$output = file_get_contents($url);
		}
		return $output;
	}
}

@header( "Content-type: text/html; charset=utf-8" );

/*
$f = new FilmReader();
echo "<pre>";
print_r( $f->get( "http://www.beyazperde.com/filmler/film-238132/" ) );
print_r( $f->get( "http://www.beyazperde.com/filmler/film-11736/" ) );
print_r( $f->get( "http://www.beyazperde.com/filmler/film-207730/" ) );
echo "</pre>";
*/

?>
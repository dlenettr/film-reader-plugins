<?php
/*
=====================================================
 Author : Mehmet Hanoğlu <dle.net.tr>
-----------------------------------------------------
 License : MIT License
-----------------------------------------------------
 Copyright (c)
-----------------------------------------------------
 Date : 30.03.2016 [1.2]
=====================================================
*/

class FilmReader {

	public function get( $url ) {
		$html = $this->getURLContent( $url );

		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		$x = new DOMXPath( $dom );
		$html = iconv( "cp1251", "utf-8", $html );

		$film = array();
		$film['url'] = $url;
		$film['name'] = $this->cleanWords( $x->query('//span[@itemprop="alternativeHeadline"]')->item(0)->nodeValue );
		$film['namelocal'] = $this->cleanWords( $x->query('//h1[@itemprop="name"]')->item(0)->nodeValue );
		$_temp = explode( "'", $this->cleanWords( $x->query('//a[@class="popupBigImage"]')->item(0)->getAttribute('onclick') ) );
		$film['orgimg'] = "http://st.kp.yandex.net" . $_temp[1];
		$film['img'] = $this->cleanWords( $x->query('//a[@class="popupBigImage"]/img')->item(0)->getAttribute('src') );
		$film['datelocal'] = $this->cleanWords( $x->query('//td[@id="div_world_prem_td2"]/div/a')->item(0)->nodeValue );
		$film['runtime'] = $this->cleanWords( $x->query('//td[@id="runtime"]/text()')->item(0)->nodeValue ); $_temp = array();
		foreach($x->query('//td[@itemprop="director"]/a') as $node) { $_temp[] =  $this->cleanWords( $node->nodeValue ); }
		$film['director'] = implode(", ", $_temp); $_temp = array();
		foreach($x->query('//td[@itemprop="musicBy"]/a') as $node) { $_temp[] =  $this->cleanWords( $node->nodeValue ); }
		$film['soundtracks'] = implode(", ", $_temp); $_temp = array();
		$film['story'] = $this->cleanWords( $x->query('//div[@itemprop="description"]')->item(0)->nodeValue );
		if ( $x->query('//meta[@itemprop="ratingValue"]')->item(0) ) $film['ratinga'] = $this->cleanWords( $x->query('//meta[@itemprop="ratingValue"]')->item(0)->getAttribute('content') );
		if ( $x->query('//span[@itemprop="ratingCount"]')->item(0) ) $film['ratingc'] = str_replace(" ", ",", $this->cleanWords( $x->query('//span[@itemprop="ratingCount"]')->item(0)->nodeValue ) );
		$film['ratingb'] = "10";
		foreach($x->query('//a[contains(@href, "genre")]') as $node) { $_temp[] =  $this->cleanWords( $node->nodeValue ); }
		$film['genres'] = implode(", ", array_filter( $_temp ) ); $_temp = array();
		$film['year'] = $this->cleanWords( $x->query('//a[contains(@href, "year")]')->item(0)->nodeValue );
		foreach($x->query('//a[contains(@href, "country")]') as $node) { $_temp[] =  $this->cleanWords( $node->nodeValue ); }
		$film['country'] = implode(", ", array_filter( $_temp ) ); $_temp = array();
		foreach($x->query('//td[@itemprop="producer"]/a') as $node) { $_temp[] =  $this->cleanWords( $node->nodeValue ); }
		$film['author'] = implode(", ", array_filter( $_temp ) );
		if ( preg_match( "#<tr><td class=\"type\">сценарий</td><td>(.+?)</td>#is", $html, $m ) ) {
			$film['actors'] = trim( trim( trim( strip_tags( $m[1] ), '.' ) ), ',' );
		}
		unset( $_temp );
		return $film;
	}

	private function cleanWords( $text ) {
		$text = str_replace( array( "\t", "  ", "\r\n", "\n", "..." ), "", $text );
		return $text;
	}

	private function getURLContent($url) {
		if ( function_exists('curl_exec') ) {
			$ch = curl_init( $url );
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.2; en-US; rv:1.8.1.15) Gecko/2008111317 Firefox/3.0.4");
			curl_setopt($ch, CURLOPT_ENCODING, "");
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
			curl_setopt($ch, CURLOPT_TIMEOUT, 120);
			curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
			$output  = curl_exec( $ch );
			$err     = curl_errno( $ch );
			$errmsg  = curl_error( $ch );
			$header  = curl_getinfo( $ch );
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
print_r( $f->get("http://www.kinopoisk.ru/film/817931/") );
print_r( $f->get("http://www.kinopoisk.ru/film/682886/") );
print_r( $f->get("http://www.kinopoisk.ru/film/806977/") );
print_r( $f->get("http://www.kinopoisk.ru/film/817481/") );
print_r( $f->get("http://www.kinopoisk.ru/film/775276/") );
echo "</pre>";
*/

?>
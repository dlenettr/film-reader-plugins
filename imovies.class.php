<?php
/*
=====================================================
 Author : Mehmet Hanoğlu <dle.net.tr>
-----------------------------------------------------
 License : MIT License
-----------------------------------------------------
 Copyright (c) 2014 DLE.NET.TR
-----------------------------------------------------
 Date : 22.04.2014 [1.0]
=====================================================
*/

class FilmReader {

	public function get( $url ) {
		$html = $this->getURLContent( $url );

		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		$x = new DOMXPath( $dom );
		$film['url'] = $this->cleanWords( $url );
		$film['name'] = $this->cleanWords( $x->query('//h2[@class="film_title notbold blue film_title_eng"]')->item(0)->nodeValue );
		$film['namelocal'] = $this->cleanWords( $x->query('//h1[@class="film_title blue"]')->item(0)->nodeValue );
		$film['year'] = $this->cleanWords( $x->query('//h3[@class="film_year"]')->item(0)->nodeValue );
		$film['namelong'] = $film['name'] . " (" . $film['year'] . ")";
		$film['country'] = $this->cleanWords( $x->query('//h3[@class="film_country1"]')->item(0)->nodeValue );
		$film['ratinga'] = $this->cleanWords( $x->query('//font[@id="imrating"]')->item(0)->nodeValue ); $_tmp = array();
		foreach($x->query('//h3[@class="film_cast"]/a[contains(@href,"watch?cat_id")]') as $node) { $_tmp[] = $this->cleanWords( $node->nodeValue ); }
		$film['genres'] = implode( ", ", $_tmp ); $_tmp = array();
		foreach($x->query('//h3[@class="film_cast"]/a[contains(@href,"watch?cat_id")]') as $node) { $_tmp[] = $this->cleanWords( $node->nodeValue ); }
		$film['genres'] = implode( ", ", $_tmp ); $_tmp = array();
		foreach($x->query('//h3[@class="film_cast" and not(contains(@id, "f_cast"))]/a[contains(@href,"cast_member")]') as $node) { $_tmp[] = $this->cleanWords( $node->nodeValue ); }
		$film['director'] = implode( ", ", $_tmp ); $_tmp = array();
		foreach($x->query('//h3[@id="f_cast"]/a[contains(@href,"cast_member")]') as $node) { $_tmp[] = $this->cleanWords( $node->nodeValue ); }
		$film['actors'] = implode( ", ", $_tmp ); $_tmp = array();
		$_tmp = explode( ":", $this->cleanWords( $x->query('//h3[6]')->item(0)->nodeValue ) );
		$film['budget'] = trim( $_tmp[1] );
		if ( $x->query('//div[@class="distributor"]/img')->item(0) ) {
			$film['img'] = $this->cleanWords( $x->query('//div[@class="distributor"]/img')->item(0)->getAttribute("src") );
			if ( ! empty( $film['img'] ) ) { $film['img'] = "http://www.imovies.ge" . $film['img']; }
			$film['orgimg'] = $film['img'];
		}
		$film['story'] = $this->cleanWords( $x->query('//p[@class="last"]/text()')->item(0)->nodeValue );

		return $film;
	}

	private function cleanWords( $text ) {
		$text = trim( str_replace( array( "\t", "  ", "\"", "\r\n", "\n", "»", "&#187;", "&raquo;" ), "", $text ) );
		return $text;
	}

	private function getURLContent($url) {
		if ( function_exists('curl_exec') ) {
			$ch = curl_init( $url );
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_ENCODING, "UTF-8");
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


?>
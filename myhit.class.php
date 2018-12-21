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

		$_tmp = $this->cleanWords( $x->query('//div[@id="hdtl"]/center/h1')->item(0)->nodeValue );
		$_tmp = explode( "/", $_tmp );
		$film['namelong'] = trim( $_tmp[1] );
		$film['namelocal'] = trim( $_tmp[0] );
		$film['name'] = preg_replace( "#(\((.+?)\))#", "", $film['namelong'] ); $_tmp = array();
		$_tmp = preg_match( "#(\((.+?)\))#", $film['namelong'], $_tmp2 );
		$film['year'] = trim( $_tmp2[2] ); unset( $_tmp2 ); $_tmp = array();
		preg_match_all("#\<font style='cursor:pointer' onclick='GetMoviesByGenre\([0-9]+,[0-9]+\)' >(.*?)</font>#", $html, $_tmp );
		$film['genres'] = implode( ", ", $_tmp[1] ); $_tmp = array();
		preg_match_all("#\<a href=\"(.+?)\" class=act >(.*?)</a>#", $html, $_tmp );
		$film['actors'] = implode( ", ", $_tmp[2] );
		$film['ratinga'] = $this->cleanWords( $x->query('//div[@class="imdb"]')->item(0)->nodeValue );
		$film['story'] = $this->cleanWords( $x->query('//td/div[12]')->item(0)->nodeValue );
		$film['img'] = $this->cleanWords( $x->query('//td/link[@rel="image_src"]')->item(0)->getAttribute("href") );
		$film['orgimg'] = $film['img'];
		return $film;
	}

	private function cleanWords( $text ) {
		$text = trim( str_replace( array( "\t", "  ", "\"", "\r\n", "\n", "»", "&#187;", "&raquo;", "See more" ), "", $text ) );
		return $text;
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


?>
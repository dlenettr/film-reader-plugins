<?php
/*
=====================================================
 Author : Mehmet Hanoğlu <dle.net.tr>
-----------------------------------------------------
 License : MIT License
-----------------------------------------------------
 Copyright (c)
-----------------------------------------------------
 Date : 02.04.2017 [2.4]
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

	public function get( $url ) {
		$html = $this->getURLContent( $url );
		$html = str_replace( array( "\n", "\r", "  " ), "", $html );

		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		$x = new DOMXPath( $dom );

		$film['url'] = $this->cleanWords( $url );
		$film['datelocal'] = $this->cleanWords( $x->query('//div[@class="meta-body-item"][1]/span[2]')->item(0)->nodeValue );
		$film['name'] = $this->cleanWords( $x->query('//meta[@itemprop="name"]')->item(0)->getAttribute("content") ); $_tmp = array();
		foreach($x->query('//span[@itemprop="director"]/a/span[@itemprop="name"]') as $node) { $_tmp[] = $this->cleanWords( $node->nodeValue ); } $_dir = $_tmp;
		$film['director'] = implode( ", ", $_tmp ); $_tmp = array();
		foreach($x->query('//a[contains(@href, "personne")]') as $node) { $_tmp[] = $this->cleanWords( $node->nodeValue ); }
		$film['actors'] = implode( ", ", array_diff( $_tmp, $_dir ) ); $_tmp = array();
		foreach($x->query('//span[@itemprop="genre"]') as $node) { $_tmp[] = $this->cleanWords( $node->nodeValue ); }
		$film['genres'] = implode( ", ", $_tmp ); $_tmp = array();
		$_tmps = array(); $_key = array(); $_val = array();
		foreach( $x->query('//div[@class="item"]/span[1]') as $node ) { $_key[] = $this->cleanWords( $node->nodeValue ); }
		foreach( $x->query('//div[@class="item"]/*[2]') as $node ) { $_val[] = $this->cleanWords( $node->nodeValue ); }
		$_tmps = array_combine( $_key, $_val );
		$film['distributor'] = ( array_key_exists( "Distributeur", $_tmps ) ) ? $_tmps["Distributeur"] : "";
		$film['year'] = ( array_key_exists( "Année de production", $_tmps ) ) ? $_tmps["Année de production"] : "";
		$film['namelocal'] = ( array_key_exists( "Titre original", $_tmps ) ) ? $_tmps["Titre original"] : "";
		$film['budget'] = ( array_key_exists( "Budget", $_tmps ) ) ? $_tmps["Budget"] : "";
		$film['language'] = ( array_key_exists( "Langues", $_tmps ) ) ? $_tmps["Langues"] : "";
		if ( empty( $film['language'] ) ) {
			$film['language'] = ( array_key_exists( "Langue", $_tmps ) ) ? $_tmps["Langue"] : "";
		}
		$film['type'] = ( array_key_exists( "Type de film", $_tmps ) ) ? $_tmps["Type de film"] : "";
		$film['color'] = ( array_key_exists( "Couleur", $_tmps ) ) ? $_tmps["Couleur"] : "";
		$film['sound'] = ( array_key_exists( "Format audio", $_tmps ) ) ? $_tmps["Format audio"] : "";
		preg_match_all( "#<div class=\"meta-body-item( with)*\">(.+?)</div>#is", mb_convert_encoding( $html, 'UTF-8', 'Windows-1252' ), $_tmp );
		foreach( end( $_tmp ) as $_tmp2 ) {
			if ( strpos( strip_tags( $_tmp2 ), "Nationalité" ) !== false ) {
				$film['country'] = trim( str_replace( array( "Nationalité", "Nationalités" ), "", strip_tags( $_tmp2 ) ) );
			}
		}
		$film['story'] = $this->cleanWords( $x->query('//div[@itemprop="description"]')->item(0)->nodeValue );
		if ( empty( $film['story'] ) ) $film['story'] = $this->cleanWords( $x->query('//div[@class="margin_20b"]/p')->item(1)->nodeValue );
		$film['productionfirm'] = $this->cleanWords( $x->query('//span[@itemprop="productionCompany"]/text()')->item(0)->nodeValue );
		$film['img'] = $this->cleanWords( $x->query('//img[@itemprop="image"]')->item(0)->getAttribute("src") );
		$film['orgimg'] = preg_replace( "#r_([0-9]+)_([0-9]+)#is", "r_400_800", $film['img'] );
		if ( $x->query('//span[@itemprop="ratingValue"]')->item(0) ) {
			$film['ratinga'] = $this->cleanWords( $x->query('//span[@itemprop="ratingValue"]')->item(0)->getAttribute("content") );
			$film['ratingb'] = $this->cleanWords( $x->query('//meta[@itemprop="bestRating"]')->item(0)->getAttribute("content") );
			$film['ratingc'] = $this->cleanWords( $x->query('//span[@itemprop="ratingCount"]')->item(0)->nodeValue );
		}
		$_tmp = $x->query('//div[@class="meta-body-item"]')->item(0)->nodeValue;
		if ( preg_match( "#\\((.+?)\\)#is", $_tmp, $match ) ) {
			$film['runtime'] = $this->cleanWords( $match[1] );
		}
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
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.2; en-US; rv:1.8.1.15) Gecko/2008111317 Firefox/3.0.4");
			curl_setopt($ch, CURLOPT_ENCODING, "Windows-1252");
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
			curl_setopt($ch, CURLOPT_TIMEOUT, 120);
			curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
			$output  = curl_exec( $ch );
			curl_close( $ch );
		} else {
			$output = file_get_contents($url);
		}
		$output = mb_convert_encoding( $output, 'Windows-1252', 'UTF-8' );
		return $output;
	}
}

@header( "Content-type: text/html; charset=utf-8" );

/*
$f = new FilmReader();
echo "<pre>";
print_r( $f->get( "http://www.allocine.fr/film/fichefilm_gen_cfilm=317.html" ) );
print_r( $f->get( "http://www.allocine.fr/film/fichefilm_gen_cfilm=182266.html" ) );
print_r( $f->get( "http://www.allocine.fr/film/fichefilm_gen_cfilm=234319.html" ) );
print_r( $f->get( "http://www.allocine.fr/film/fichefilm_gen_cfilm=190965.html" ) );
echo "</pre>";
*/

?>
<?php
/*
=====================================================
 Author : Mehmet Hanoğlu <dle.net.tr>
-----------------------------------------------------
 License : MIT License
-----------------------------------------------------
 Copyright (c) 2015
-----------------------------------------------------
 Date : 06.06.2015 [1.5]
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

	public function get( $url ) {
		$html = $this->getURLContent( $url );

		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		$x = new DOMXPath( $dom );

		$film = array();
		foreach($x->query("//meta") as $node) { $_tmp['meta'][$node->getAttribute("property")] = $node->getAttribute("content"); }
		preg_match("/\(([0-9]+)\)/",  $_tmp['meta']['og:title'], $year, PREG_OFFSET_CAPTURE);
		$film = array(
			'namelong'		=> $_tmp['meta']['og:title'],
			'url'			=> $_tmp['meta']['og:url'],
			'year'			=> $year[1][0],
		);
		unset( $name );

		$film['ratinga'] = str_replace( ".", ",", $this->cleanWords( $x->query('//span[@itemprop="ratingValue"]')->item(0)->nodeValue ) );
		$film['ratingb'] = "10";
		$film['ratingc'] = $this->cleanWords( $x->query('//span[@itemprop="ratingCount"]')->item(0)->nodeValue );
		$film['img'] = $this->cleanWords( $x->query('//img[@itemprop="image"]')->item(0)->getAttribute('src') );
		$_temp = explode("'", $this->cleanWords( $x->query('//a[@class="pointer"]')->item(0)->getAttribute('onclick') ) );
		$film['orgimg'] = $_temp[1];
		$film['name'] = $this->cleanWords( $x->query('//h2[@class="w-auto"]/small')->item(0)->nodeValue );
		$film['namelocal'] = $this->cleanWords( $x->query('//h1[@class="fl"]')->item(0)->nodeValue );
		if ( empty( $film['name'] ) ) $film['name'] = $film['namelocal'];

		$_tmp = array();
		preg_match_all( "#<p>(.+?)<\/p>#is", $html, $matches );
		foreach( $matches[1] as $m ) {
			$_text = $this->cleanWords( strip_tags( $m ) );
			$_tmp2 = explode( ":", $_text );
			$_tmp2[0] = str_replace( " ", "_", trim( $_tmp2[0] ) );
			$_tmp[ $_tmp2[0] ] = str_replace( "&nbsp;", "", trim( $_tmp2[1] ) );
		}

		if ( array_key_exists( "Vizyon_Tarihi", $_tmp ) ) $film['datelocal'] = $_tmp['Vizyon_Tarihi'];
		if ( array_key_exists( "Süre", $_tmp ) ) $film['runtime'] = $_tmp['Süre'];
		if ( array_key_exists( "Yapımı", $_tmp ) ) $film['country'] = preg_replace( "#([0-9\s\-]+)#is", "", $_tmp['Yapımı'] );
		if ( array_key_exists( "Yapımcı", $_tmp ) ) $film['author'] = str_replace( " , ", ", ", $_tmp['Yapımcı'] );
		if ( array_key_exists( "Senaryo", $_tmp ) ) $film['writers'] = $_tmp['Senaryo'];
		if ( array_key_exists( "Oyuncular", $_tmp ) ) $film['actors'] = $_tmp['Oyuncular'];
		// [Yönetmen] => Lee Toland Krieger
		$film['director'] = $this->cleanWords( $x->query('//span[@itemprop="director"]/a/span[@itemprop="name"]')->item(0)->nodeValue );
		$film['story'] = $this->cleanWords( $x->query('//p[@itemprop="description"]')->item(0)->nodeValue ); $_temp = array();
		foreach($x->query('//span') as $node) { if ( $node->getAttribute("itemprop") == "genre" ) { $_temp[] =  $node->nodeValue; } }
		$film['genres'] = implode(", ", $_temp);$_temp = array();

		foreach($x->query('//p/a[@itemprop="producer"]') as $node) { $_temp[] =  $node->nodeValue; }
		$_temp = array();
		foreach($x->query('//div/a[@itemprop="url"]/text()') as $node) { $_temp[] =  $node->nodeValue; }
		foreach($x->query('//div[@class="galeri film-galeri grid8"]/a/img') as $node) {
			$film['thumbs'][] =  $node->getAttribute('src');
			$film['screens'][] =  str_replace("/ss_kare/", "/ss_buyuk/", $node->getAttribute('src') );
		}

		unset( $_temp );
		return $film;
	}

	private function cleanWords( $text ) {
		$text = str_replace( array( "\t", "  ", "\r\n", "\n" ), "", $text );
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

/*
$f = new FilmReader();
echo "<pre>";
print_r( $f->get( "http://www.sinemalar.com/film/94628/the-age-of-adaline" ) );
print_r( $f->get( "http://www.sinemalar.com/film/220150/beni-de-gotur" ) );
print_r( $f->get( "http://www.sinemalar.com/film/222443/insidious-chapter-3" ) );
echo "</pre>";
*/

?>
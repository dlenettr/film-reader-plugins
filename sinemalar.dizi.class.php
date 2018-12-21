<?php
/*
=====================================================
 Author : Mehmet Hanoğlu  http://dle.net.tr
-----------------------------------------------------
 License : MIT License
-----------------------------------------------------
 Copyright (c) 2014
-----------------------------------------------------
 Date : 18.08.2014
=====================================================
*/

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
		preg_match("#<a href=\"http\:\/\/www\.sinemalar\.com\/filmler\/([a-z]+)\" title=\"([A-Z]+) Filmleri\">([A-Z]+)<\/a>#", $html, $matches );
		$film['country'] = $matches[3];
		$film['ratinga'] = str_replace( ".", ",", $this->cleanWords( $x->query('//span[@itemprop="ratingValue"]')->item(0)->nodeValue ) );
		$film['ratingb'] = "10";
		$film['ratingc'] = $this->cleanWords( $x->query('//span[@itemprop="ratingCount"]')->item(0)->nodeValue );
		$film['img'] = $this->cleanWords( $x->query('//img[@itemprop="image"]')->item(0)->getAttribute('src') );
		$_temp = explode("'", $this->cleanWords( $x->query('//a[@class="pointer"]')->item(0)->getAttribute('onclick') ) );
		$film['orgimg'] = $_temp[1];
		$film['name'] = $this->cleanWords( $x->query('//h1[@class="fl"]')->item(0)->nodeValue );
		$film['namelocal'] = $this->cleanWords( $x->query('//h1[@class="fl"]')->item(0)->nodeValue );
		if ( empty( $film['name'] ) ) $film['name'] = $film['namelocal'];
		$film['runtime'] = str_replace( array("Süre:","."), "", $this->cleanWords( $x->query('//*[@id="container"]/div[3]/div[3]/article[1]/div/div[2]/p[3]')->item(0)->nodeValue ) );
		$film['director'] = $this->cleanWords( $x->query('//span[@itemprop="director"]/a/span[@itemprop="name"]')->item(0)->nodeValue );
		$film['story'] = $this->cleanWords( $x->query('//p[@itemprop="description"]')->item(0)->nodeValue ); $_temp = array();
		foreach($x->query('//span') as $node) { if ( $node->getAttribute("itemprop") == "genre" ) { $_temp[] =  $node->nodeValue; } }
		$film['genres'] = implode(", ", $_temp);$_temp = array();
		foreach($x->query('//span[@itemprop="actors"]/a/span[@itemprop="name"]') as $node) { $_temp[] =  $node->nodeValue; }
		$film['actors'] = implode(", ", $_temp);$_temp = array();
		foreach($x->query('//p/a[@itemprop="producer"]') as $node) { $_temp[] =  $node->nodeValue; }
		$film['author'] = str_replace( "Yapımcı : ", "", $this->cleanWords( $x->query('//*[@id="container"]/div[3]/div[3]/article[1]/div/div[2]/p[8]')->item(0)->nodeValue ) ); $_temp = array();
		foreach($x->query('//div[@class="grid6 relative mh335"]/p/a') as $node) { if ( strpos( $node->getAttribute('onclick'), "Senariste" ) !== false ) { $_temp[] =  $node->nodeValue; } }
		$film['writers'] = implode(", ", $_temp);$_temp = array();
		foreach($x->query('//div/a[@itemprop="url"]/text()') as $node) { $_temp[] =  $node->nodeValue; }
		foreach($x->query('//div[@class="galeri film-galeri grid8"]/a/img') as $node) { 
			$film['thumbs'][] =  $node->getAttribute('src');
			$film['images'][] =  str_replace("/ss_kare/", "/ss_buyuk/", $node->getAttribute('src') );
		}
		$film['sound'] = str_replace( "Seslendirenler : ", "", $this->cleanWords( $x->query('//*[@id="container"]/div[3]/div[3]/article[1]/div/div[2]/p[6]')->item(0)->nodeValue ) );

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

?>
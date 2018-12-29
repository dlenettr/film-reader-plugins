<?php
/*
=====================================================
 Author : Mehmet Hanoğlu <dle.net.tr>
-----------------------------------------------------
 License : MIT License
-----------------------------------------------------
 Copyright (c)
-----------------------------------------------------
 Date : 30.12.2018 [1.6]
=====================================================
*/

error_reporting(E_ALL ^ E_WARNING);
ini_set('display_errors', 1);

class FilmReader {

	public function get( $url ) {
		$html = $this->getURLContent( $url );

		preg_match( "#\=([0-9]+)\.#is", $url, $_tmp );
		$film_id = trim( $_tmp[1] );

		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		$x = new DOMXPath( $dom );

		$film['url'] = $this->cleanWords( $url );
		$film['name'] = $this->cleanWords( $x->query('//meta[@property="og:title"]')->item(0)->getAttribute("content") );
		$film['img'] = $this->cleanWords( $x->query('//meta[@property="og:image"]')->item(0)->getAttribute("content") );
		$film['orgimg'] = $this->cleanWords( $x->query('//meta[@property="og:image"]')->item(0)->getAttribute("content") );
		$_tmp = [];
		foreach($x->query('//div[@class="meta-body-item serie-genre"]/span/div') as $node) { $_tmp[] = $this->cleanWords( $node->nodeValue ); }
		$film['genres'] = implode( ", ", $_tmp );
		foreach($x->query('//div[@class="stats-numbers-row-item"]') as $node) {
			$v = $this->cleanWords( $node->nodeValue );
			if ( strpos( $v, "Saisons" ) !== false ) $film['seasons'] = str_replace( "Saisons", "", $v );
			else if ( strpos( $v, "Episodes" ) !== false ) $film['episodes'] = str_replace( "Episodes", "", $v );
		}
		$film['story'] = $this->cleanWords( $x->query('//div[contains(@class,"content-txt")]')->item(0)->nodeValue );

		$_tmp1 = [];
		foreach($x->query('//a[contains(@href,"/personne/")]') as $node) {
			$_tmp1[] = $this->cleanWords( $node->nodeValue );
		}
		$_tmp2 = [];
		foreach($x->query('//div[contains(@class,"card card-person")]/div/div[@class="meta-title"]/*') as $node) {
			$_tmp2[] = $this->cleanWords( $node->nodeValue );
		}

		$film['actors'] = implode(", ", $_tmp2);
		$film['director'] = implode(", ", array_diff($_tmp1,$_tmp2));

		preg_match("#[0-9]{4}#s", $this->cleanWords( $x->query('//div[@class="meta-body-item meta-body-info"]')->item(0)->nodeValue ), $m);
		$film['year'] = $m[0];

		//$film['year'] = $this->cleanWords( trim( $x->query('//div[@class="serie-production"]/span')->item(0)->nodeValue, '()' ) );
		$film['country'] = $this->cleanWords( $x->query('//span[contains(@class,"nationality")]')->item(0)->nodeValue );
		if ( $x->query('//div[@class="serie-production"]/span')->item(1) ) {
			$film['runtime'] = $this->cleanWords( $x->query('//div[@class="serie-production"]/span')->item(1)->nodeValue );
		}
		if ( $x->query('//span[@class="stareval-note"]')->item(1) ) {
			$film['ratinga'] = $this->cleanWords( $x->query('//span[@class="stareval-note"]')->item(1)->nodeValue );
		}
		$film['ratingb'] = "10";
		if ( $x->query('//span[@class="stareval-review"]')->item(1) ) {
			$film['ratingc'] = strstr( $this->cleanWords( $x->query('//span[@class="stareval-review"]')->item(1)->nodeValue ), ' ', true);
		}
		$film['status'] = $this->cleanWords( $x->query('//div[contains(@class,"label-status")]')->item(0)->nodeValue );

		if ( $x->query('//div[@class="meta-body-item meta-body-info"]')->length > 0 ) {
			$_tmp = $this->cleanWords( $x->query('//div[@class="meta-body-item meta-body-info"]')->item(0)->nodeValue );
			$_tmp = explode('/', $_tmp);
			if ( count( $_tmp ) > 0 && strpos($_tmp[0], '201') !== false ) $film['year'] = $_tmp[0];
			if ( count( $_tmp ) > 1 && strpos($_tmp[1], 'min') !== false ) $film['runtime'] = $_tmp[1];
			if ( count( $_tmp ) > 2 ) $film['genres'] = $_tmp[2];
		}

		$html = $this->getURLContent("http://www.allocine.fr/series/ficheserie-{$film_id}/critiques/");

		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		$x = new DOMXPath( $dom );

		$film['ratingc'] = $this->cleanWords( $x->query('//span[@itemprop="ratingCount"]')->item(0)->nodeValue );

		return $film;
	}

	private function cleanWords( $text ) {
		$text = trim( str_replace( array( "\t", "  ", "\"", "\r\n", "\n", "»", "&#187;", "&raquo;" ), "", $text ) );
		return $text;
	}

	private function getURLContent($url) {
		$ch = curl_init( $url );
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

@header( "Content-type: text/html; charset=utf-8" );

/*
$f = new FilmReader();
echo "<pre>";
//print_r( $f->get( "http://www.allocine.fr/series/ficheserie_gen_cserie=17358.html" ) );
//print_r( $f->get( "http://www.allocine.fr/series/ficheserie_gen_cserie=19239.html" ) );
//print_r( $f->get( "http://www.allocine.fr/series/ficheserie_gen_cserie=22118.html" ) );
//print_r( $f->get( "http://www.allocine.fr/series/ficheserie_gen_cserie=21504.html" ) );
//print_r( $f->get( "http://www.allocine.fr/series/ficheserie_gen_cserie=23381.html" ) );
echo "</pre>";
*/
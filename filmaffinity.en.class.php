<?php
/*
=====================================================
 Author : Mehmet Hanoğlu <dle.net.tr>
-----------------------------------------------------
 License : MIT License
-----------------------------------------------------
 Copyright (c)
-----------------------------------------------------
 Date : 14.04.2017 [1.3]
=====================================================
*/

class FilmReader {

	public function get( $url ) {
		$html = $this->getURLContent( $url );

		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		$x = new DOMXPath( $dom );

		$film = array();
		$film['url'] = $url;
		$_temp = $x->query('//title')->item(0)->nodeValue;
		$_temp = explode( " - ", $_temp );
		$film['namelong'] = $this->cleanWords( $_temp[0] );
		$film['namelocal'] = $this->cleanWords( $x->query('//span[@itemprop="name"]')->item(0)->nodeValue );
		$film['img'] = $this->cleanWords( $x->query('//img[@itemprop="image"]')->item(0)->getAttribute('src') );
		$film['orgimg'] = $this->cleanWords( $x->query('//a[@class="lightbox"]')->item(0)->getAttribute('href') );
		$film['ratinga'] = $this->cleanWords( $x->query('//div[@itemprop="ratingValue"]')->item(0)->nodeValue );
		$film['ratingb'] = $this->cleanWords( $x->query('//meta[@itemprop="bestRating"]')->item(0)->getAttribute("content") );
		$film['ratingc'] = $this->cleanWords( $x->query('//span[@itemprop="ratingCount"]')->item(0)->nodeValue );

		$_key = array();
		foreach( $x->query('//dl[@class="movie-info"]/dt') as $node ) {
			$_key[] = $this->cleanWords( $node->nodeValue );
		}
		$_val = array();
		foreach( $x->query('//dl[@class="movie-info"]/dd') as $node ) {
			$_val[] = $this->cleanWords( $node->nodeValue );
		}
		$_temp = array_combine( $_key, $_val );

		$film['nameaka'] = $_temp['AKA'];
		$film['cinematography'] = $_temp['Cinematography'];
		$film['moviegroups'] = $_temp['Movie Groups'];
		$film['actors'] = $_temp['Cast'];
		$film['name'] = substr( $_temp['Original title'], 0, -3 );
		$film['writers'] = $_temp['Screenwriter'];
		$film['year'] = $_temp['Year'];
		$film['runtime'] = $_temp['Running Time'];
		$film['country'] = $_temp['Country'];
		$film['director'] = $_temp['Director'];
		$film['soundtracks'] = $_temp['Music'];
		$film['productionfirm'] = $_temp['Producer'];
		$film['genres'] = str_replace( "  ", " ", str_replace( array(".", " | "), ", ", $_temp['Genre'] ) );
		$film['story'] = str_replace( "(FILMAFFINITY)", "", $_temp['Synopsis / Plot'] );

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
			curl_setopt($ch, CURLOPT_HEADER, true);
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
print_r( $f->get( "http://www.filmaffinity.com/en/film236888.html" ) );
print_r( $f->get( "http://www.filmaffinity.com/en/film809297.html" ) );
print_r( $f->get( "http://www.filmaffinity.com/en/film730528.html" ) );
print_r( $f->get( "http://www.filmaffinity.com/en/film695552.html" ) );
print_r( $f->get( "http://www.filmaffinity.com/en/film161026.html" ) );
print_r( $f->get( "http://www.filmaffinity.com/en/film160882.html" ) );
echo "</pre>";
*/

?>
<?php
/*
=====================================================
 Author : Mehmet Hanoğlu <www.marzochi.ws>
-----------------------------------------------------
 License : MIT License
-----------------------------------------------------
 Copyright (c) 2013 MaRZoCHi.WS
-----------------------------------------------------
 Date : 09.11.2013 [1.4]
=====================================================
*/

class FilmReader {

	public function get( $url ) {
		$html = $this->getURLContent( $url );

		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		$x = new DOMXPath( $dom ); 

		$film = array();
		$film['namelocal'] = $this->cleanWords( $x->query('//span[@class="title-pt"]/text()')->item(0)->nodeValue );
		$film['name'] = $this->cleanWords( $x->query('//span[@class="title-original"]/text()')->item(0)->nodeValue );
		preg_match_all("(\<img src=\"(.+?)\" alt=\"Poster(.+?))i", $html, $imgs);
		$film['img'] = $imgs[1][0];
		$film['url'] = $url;
		$film['runtime'] = $this->cleanWords( $x->query('//dd[@class="minor info-duration"]')->item(0)->nodeValue );
		$film['genres'] = $this->cleanWords( $x->query('//dd[@class="minor info-genero"]/a/text()')->item(0)->nodeValue );
		$film['year'] = $this->cleanWords( $x->query('//dd[@class="minor info-year"]')->item(0)->nodeValue );
		$film['ratinga'] = $this->cleanWords( $x->query('//strong[@class="score"]')->item(0)->nodeValue );
		$film['ratinga'] = str_replace( ".", ",", $film['ratinga'] );
		$film['ratingc'] = "5";
		$film['country'] = $this->cleanWords( $x->query('//td[@class="origem"]/span')->item(0)->nodeValue );
		preg_match_all("(\<p class=\"sinopse closed.*?\"\>\<p\>(.+?)\<\/p\>\<\/p\>)im", $this->cleanWords( $html ), $story);
		$film['story'] = str_replace( array("</p>", "<p>"), "", $story[1][0] );
		preg_match_all("(o\<\/dt\>\<dd\>(.+?)\<\/dd\>)i", $this->cleanWords( $html ), $direc); $_temp = array();
		foreach($x->query('//dd/span[@class="vcard"]/a') as $node) { $_temp[] =   $this->cleanWords( $node->nodeValue ); }
		unset( $_temp[0] ); // First object is Director
		$film['actors'] = implode(", ", $_temp);
		unset($dom);
		$dom = new DOMDocument();
		@$dom->loadHTML($direc[1][0]);
		$x = new DOMXPath($dom);
		$directors = array();
		foreach ( $x->query('//span/a') as $node ) { $directors[] = $this->cleanWords( $node->nodeValue ); }
		$film["director"] = implode(", ", $directors);
		$film['type'] = "video.movie";
		
		return $film;
	}

	private function cleanWords( $text ) {
		$text = trim( str_replace( array( "\t", "  ", "\r\n", "\n" ), "", $text ) );
		$text = str_replace( "|", ", ", $text );
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

//$f = new FilmReader();
//print_r( $f->get("http://cinema.sapo.pt/filme/ate-amanha-camaradas") );
?>
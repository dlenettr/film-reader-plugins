<?php
/*
=====================================================
 Author : Mehmet Hanoğlu <dle.net.tr>
-----------------------------------------------------
 License : MIT License
-----------------------------------------------------
 Copyright (c) 2014
-----------------------------------------------------
 Date : 21.08.2014 [1.0]
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
		$film['story'] = $this->cleanWords( $x->query('//div[@class="contenttext"]/text()')->item(0)->nodeValue );
		$film['datelocal'] = $this->cleanWords( $x->query('//div[@class="year"]')->item(0)->nodeValue );
		$film['country'] = $this->cleanWords( $x->query('//div[@class="country"]')->item(0)->nodeValue );
		$film['director'] = $this->cleanWords( $x->query('//div[@class="director"]')->item(0)->nodeValue );
		$film['year'] = $this->cleanWords( $x->query('//div[@class="release"]')->item(0)->nodeValue );
		$film['runtime'] = $this->cleanWords( $x->query('//div[@title="Durée du film"]')->item(0)->nodeValue );
		$film['img'] = "http://filmonde.net" . $this->cleanWords( $x->query('//img[@class="full-poster"]')->item(0)->getAttribute('src') );
		$film['name'] = $this->cleanWords( $x->query('//h2[@class="title"]/text()')->item(0)->nodeValue );
		$_tmp = array(); foreach( $x->query('//div[@class="cats"]/a') as $node ) { $_tmp[] = $this->cleanWords( $node->nodeValue ); }
		$film['genres'] = implode( ", ", $_tmp );
		$_tmp = array(); foreach( $x->query('//div[@class="actor"]/a') as $node ) { $_tmp[] = $this->cleanWords( $node->nodeValue ); }
		$film['actors'] = implode( ", ", $_tmp ); $streams = array();
		if ( $x->query('//div[@id="tab1"]/center/iframe')->item(0) ) { $streams[] = $this->cleanWords( $x->query('//div[@id="tab1"]/center/iframe')->item(0)->getAttribute('src') ) . "," . $this->cleanWords( $x->query('//a[@href="#tab1"]/span/text()')->item(0)->nodeValue ); }
		if ( $x->query('//div[@id="tab2"]/center/iframe')->item(0) ) { $streams[] = $this->cleanWords( $x->query('//div[@id="tab2"]/center/iframe')->item(0)->getAttribute('src') ) . "," . $this->cleanWords( $x->query('//a[@href="#tab2"]/span/text()')->item(0)->nodeValue ); }
		if ( $x->query('//div[@id="tab3"]/center/iframe')->item(0) ) { $streams[] = $this->cleanWords( $x->query('//div[@id="tab3"]/center/iframe')->item(0)->getAttribute('src') ) . "," . $this->cleanWords( $x->query('//a[@href="#tab3"]/span/text()')->item(0)->nodeValue ); }
		if ( $x->query('//div[@id="tab4"]/center/iframe')->item(0) ) { $streams[] = $this->cleanWords( $x->query('//div[@id="tab4"]/center/iframe')->item(0)->getAttribute('src') ) . "," . $this->cleanWords( $x->query('//a[@href="#tab4"]/span/text()')->item(0)->nodeValue ); }
		if ( $x->query('//div[@id="tab5"]/center/iframe')->item(0) ) { $streams[] = $this->cleanWords( $x->query('//div[@id="tab5"]/center/iframe')->item(0)->getAttribute('src') ) . "," . $this->cleanWords( $x->query('//a[@href="#tab5"]/span/text()')->item(0)->nodeValue ); }
		if ( $x->query('//div[@id="tab6"]/center/iframe')->item(0) ) { $streams[] = $this->cleanWords( $x->query('//div[@id="tab6"]/center/iframe')->item(0)->getAttribute('src') ) . "," . $this->cleanWords( $x->query('//a[@href="#tab6"]/span/text()')->item(0)->nodeValue ); }
		$_tmp = array(); foreach( $x->query('//div[@id="section-Downloads"]/div/a') as $node ) { $_tmp[] = array( $this->cleanWords( $node->getAttribute('href') ), $this->cleanWords( $node->nodeValue ) ); }
		$links = "###" . "Download Links" . "NEWLINE";
		foreach ( $_tmp as $data ) {
			$links .= $data[0] . "," . $data[1] . "NEWLINE";
		}
		$film = array_merge( $film, array( 'links' => $links ), array( 'streams' => implode( "NEWLINE", $streams ) ) );

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
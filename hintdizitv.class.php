<?php
/*
=====================================================
 Author : Mehmet Hanoğlu <dle.net.tr>
-----------------------------------------------------
 License : MIT License
-----------------------------------------------------
 Copyright (c)
-----------------------------------------------------
 Date : 16.02.2018 [1.1]
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
		$html = str_replace( "/>", ">", $html );
		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		$x = new DOMXPath( $dom );

		$film = [];
		if ( $x->query('//img[@class="afis"]')->item(0) ) {
			$film['img'] = $this->cleanWords( $x->query('//img[@class="afis"]')->item(0)->getAttribute('src') );
		}
		$_tmp = $dom->saveHTML( $x->query('//div[@class="kim"]')->item(1) );
		$_tmp = explode( "</font>", $_tmp );
		$_tmp = explode( "<br>", $_tmp[1] );
		$film['story'] = $this->cleanWords( strip_tags( $_tmp[0] ) );
		$film['name'] = $this->cleanWords( $x->query('//div[@class="title"]')->item(0)->nodeValue );

		if ( $x->query('//div[@class="kimdizi"]/li')->item(0) ) {
			$film['img'] = $this->cleanWords( $x->query('//div[@class="kimdizi"]/li/a/img')->item(0)->getAttribute('src') );
			$film['name'] = $this->cleanWords( $x->query('//div[@class="kimdizi"]/li/b/a')->item(0)->nodeValue );
		}

		if ( $x->query('//div[@style="font-family:Verdana;display:inherit;"]/p')->item(0) ) {
			$film['story'] = $this->cleanWords( $x->query('//div[@style="font-family:Verdana;display:inherit;"]/p')->item(0)->nodeValue );
		}

		$_tmp = [];
		foreach( $x->query('//ul[@class="film-oyuncu-blg"]/li/b/a') as $node ) {
			$_tmp[] = $this->cleanWords( $node->nodeValue );
		}

		$film['actors'] = implode( ", ", $_tmp );
		$film['year'] = $this->cleanWords( $x->query('//a[contains(@href,"sort=yil")]')->item(0)->nodeValue );
		$film['ratinga'] = $this->cleanWords( $x->query('//a[contains(@href,"sort=imdb")]')->item(0)->nodeValue );

		$_tmp = [];
		foreach( $x->query('//a[contains(@href,"tlist/")]') as $node ) {
			$_tmp[] = mb_convert_case( $this->cleanWords( $node->nodeValue ), MB_CASE_TITLE );
		}
		$film['genres'] = implode( ", ", $_tmp );

		if ( preg_match( "#hfc([0-9]+)\.html#is", $url, $match ) ) {
			$url = "http://www.hintdizi.tv/k/" . $match[1];

			$html = $this->getURLContent( $url );
			$html = htmlspecialchars_decode( $html );
			$dom = new DOMDocument();
			@$dom->loadHTML( $html );
			$x = new DOMXPath( $dom );
			$meta = "<meta charset=\"utf-8\" />";

			$titles = [];
			$src = [];
			$counter = 1;
			if ( preg_match_all( "#\>([A-Z0-9\s\wİŞÇÖÜĞışçöüğ]+)\<\/li\>#is", $html, $match ) ) {
				foreach( $match[1] as $title ) {
					if ( in_array( $title, $titles ) ) {
						$titles[] = $title . " " . $counter++;
					} else {
						$titles[] = $title;
					}
				}
			}
			if ( preg_match_all( "#<iframe.*?src=\"(.+?)\"#is", $html, $match ) ) {
				$src = $match[1];
			}

			$parts = array_combine( $titles, $src );

			$_tmp_html = "";
			foreach ( $parts as $part_title => $part_src ) {
				$_tmp_html .= "[part=" . $part_title . "]" . "<iframe src='" . $part_src . "' scrolling='no' frameborder='0' width='100%' height='100%' allowfullscreen></iframe>" . "[/part]\n";
			}

			$film['parts'] = $_tmp_html;
		}

		return $film;

	}

	private function cleanWords( $text ) {
		$text = trim( str_replace( array( "\t", "  ", "\"", "\r\n", "\n", "»", "&#187;", "&raquo;" ), "", $text ) );
		return $text;
	}

	private function getURLContent($url, $ref = "") {
		if ( function_exists('curl_exec') ) {
			$ch = curl_init( $url );
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:59.0) Gecko/20100101 Firefox/59.0");
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

@header( "Content-type: text/html; charset=utf-8" );

/*
$f = new FilmReader();
echo "<pre>";
print_r( $f->get( "http://www.hintdizi.tv/prince-wtv-hfc3254.html" ) );
print_r( $f->get( "http://www.hintdizi.tv/padmavati-padmaavat-wtv-hfc7899.html" ) );
print_r( $f->get( "http://www.hintdizi.tv/mappillai-wtv-hfc8246.html" ) );
print_r( $f->get( "http://www.hintdizi.tv/saraswatichandra-431-bolum-wtv-hfc5083.html" ) );
print_r( $f->get( "http://www.hintdizi.tv/ah-kalbim-kumkum-bhagya-118-bolum-wtv-hfc8291.html" ) );
print_r( $f->get( "http://www.hintdizi.tv/geet-hui-sabse-parayi-448-bolum-wtv-hfc4306.html" ) );
print_r( $f->get( "http://www.hintdizi.tv/kordugum-uttaran-194-bolum-wtv-hfc8303.html" ) );
echo "</pre>";
*/

?>





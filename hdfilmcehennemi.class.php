<?php
/*
=====================================================
 Author : Mehmet Hanoğlu <dle.net.tr>
-----------------------------------------------------
 License : MIT License
-----------------------------------------------------
 Copyright (c)
-----------------------------------------------------
 Date : 19.10.2017 [1.0]
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

		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		$x = new DOMXPath( $dom );
		$meta = "<meta charset=\"utf-8\" />";

		$film = [];

		$_tmp = [];
		foreach( $x->query('//ul[@class="hdc-parts"]/li') as $li ) {

			$_li = $dom->saveHTML( $li );
			$_li_h = "<!DOCTYPE html><html><head>" . $meta . "</head><body>" . $_li . "</body></html>";
			$dom2 = new DOMDocument();
			@$dom2->loadHTML( $_li_h );
			$y = new DOMXPath( $dom2 );

			$href = $this->cleanWords( $y->query('//a')->item(0)->getAttribute('href') );
			$title = $this->cleanWords( $y->query('//a/span')->item(0)->nodeValue );

			if ( strpos( $href, "?part") !== false ) continue;

			$_tmp[ $href ] = ['title' => $title];

			$dom3 = new DOMDocument();
			$dom3->loadHTML( $this->getURLContent( $href ) );
			$z = new DOMXPath( $dom3 );

			$_tmp[ $href ]['src'] = $z->query('//div[@id="player"]/iframe')->item(0)->getAttribute('src');

		}

		$_tmp_html = "";
		foreach ( $_tmp as $part ) {
			$_tmp_html .= "[part=" . $part['title'] . "]" . "<iframe src='" . $part['src'] . "' scrolling='no' frameborder='0' width='100%' height='100%' allowfullscreen></iframe>" . "[/part]NEWLINENEWLINE";
		}

		$film['parts'] = $_tmp_html;

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

header( "Content-type: text/html; charset=utf-8" );

/*
$f = new FilmReader();
echo "<pre>";
print_r( $f->get( "https://www.hdfilmcehennemi.com/sarisin-bomba-izle/" ) );
print_r( $f->get( "https://www.hdfilmcehennemi.com/american-made-izle-hd-korsub/" ) );
print_r( $f->get( "https://www.hdfilmcehennemi.com/kromit-operasyonu-izle/" ) );
echo "</pre>";
*/

?>
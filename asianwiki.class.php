<?php
/*
=====================================================
 Author : Mehmet Hanoğlu <dle.net.tr>
-----------------------------------------------------
 License : MIT License
-----------------------------------------------------
 Copyright (c)
-----------------------------------------------------
 Date : 29.11.2016 [1.0]
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
		$html = str_replace( array( "\r", "\n" ), "", $this->getURLContent( $url ) );

		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		$x = new DOMXPath( $dom );
		$meta = "<meta charset=\"utf-8\" />";

		$film = array();
		$film['url'] = $url;
		$film['title'] = $this->cleanWords( $x->query('//article/h1')->item(0)->nodeValue );
		preg_match("#<span id=\"w4g_rb_area-1\">Current user rating: <b>([0-9]+)/100</b> \(([0-9]+) votes\)<\/span>#is", $html, $_tmp );
		$film['ratinga'] = $_tmp[1];
		$film['ratingb'] = "100";
		$film['ratingc'] = $_tmp[2];
		$film['img'] = "http://asianwiki.com/" . $this->cleanWords( $x->query('//img[@class="thumbimage"]')->item(0)->getAttribute('src') );

		$_tmp = array();
		foreach( $x->query('//div[@id="mw-content-text"]/ul/li') as $node ) {
			$_li = $dom->saveHTML( $node );
			$_li_h = "<!DOCTYPE html><html><head>" . $meta . "</head><body>" . $_li . "</body></html>";
			$dom2 = new DOMDocument(); @$dom2->loadHTML( $_li_h ); $y = new DOMXPath( $dom2 );
			$_key = trim( $this->cleanWords( $y->query('//b')->item(0)->nodeValue ) );
			$_val = str_replace( "<b>" . $_key . "</b>", "", $_li );
			$_val = str_replace( array( "<li>", "</li>"), "", $_val );
			$_key = str_replace( ":", "", $_key );
			$_key = str_replace( " ", "", ucwords( $_key ) );
			$_tmp[ $_key ] = trim( $this->cleanWords( $_val ) );
		}

		$film['director'] = strip_tags( $_tmp['Director'] );
		$film['writers'] = strip_tags( $_tmp['Writer'] );
		$film['productionfirm'] = strip_tags( $_tmp['Network'] );
		$film['episodes'] = strip_tags( $_tmp['Episodes'] );
		$film['vision'] = strip_tags( $_tmp['ReleaseDate'] );
		$film['runtime'] = strip_tags( $_tmp['Runtime'] );
		$film['language'] = strip_tags( $_tmp['Language'] );
		$film['country'] = strip_tags( $_tmp['Country'] );
		preg_match("#<h2> <span class=\"mw-headline\" id=\"Plot\">Plot</span></h2>(.+?)<h2> <span class=\"mw-headline\" id=\"(Notes|Cast)\">(Notes|Cast)</span></h2>#is", $html, $_tmp );
		$film['story'] = strip_tags( str_replace( "</p><p>", "\n", $this->cleanWords( $_tmp[1] ) ) );

		$_tmp = array();
		foreach( $x->query('//div[@id="mw-content-text"]/table[2]/tr[3]/td/a') as $node ) {
			if ( count( $_tmp ) == 3 ) break;
			$_tmp[] = $this->cleanWords( $node->nodeValue );
		}
		$film['actors'] = implode( ", ", $_tmp );

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

@header( "Content-type: text/html; charset=utf-8" );

/*
$f = new FilmReader();
echo "<pre>";
print_r( $f->get( "http://asianwiki.com/Descendants_of_the_Sun" ) );
print_r( $f->get( "http://asianwiki.com/The_Age_of_Shadows" ) );
print_r( $f->get( "http://asianwiki.com/Vanilla_Boy:_Tomorrow_Is_Another_Day" ) );
print_r( $f->get( "http://asianwiki.com/Emi-Abi" ) );
print_r( $f->get( "http://asianwiki.com/Over_the_Fence" ) );
echo "</pre>";
*/


?>
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
		$film['story'] = $this->cleanWords( $x->query('//div[@id="video_title"]/h3/a')->item(0)->nodeValue );

		$_tmp = array();
		foreach( $x->query('//div[@id="video_info"]/div/table/tr') as $node ) {
			$_td = $dom->saveHTML( $node );
			$_td_h = "<!DOCTYPE html><html><head>" . $meta . "</head><body>" . $_td . "</body></html>";
			$dom2 = new DOMDocument(); @$dom2->loadHTML( $_td_h ); $y = new DOMXPath( $dom2 );
			$_key = trim( $this->cleanWords( $y->query('//td[1]')->item(0)->nodeValue ) );
			$node2 = $y->query('//td[2]')->item(0);
			$_val = trim( $this->cleanWords( $node2->nodeValue ) );
			$_key = str_replace( ":", "", $_key );
			$_key = str_replace( " ", "", ucwords( $_key ) );
			$_key = str_replace( array( "(", ")"), "", $_key );

			if ( $_key == "Genres" ) {
				$_td2 = $dom2->saveHTML( $node2 );
				$_td2_h = "<!DOCTYPE html><html><head>" . $meta . "</head><body>" . $_td2 . "</body></html>";
				$dom3 = new DOMDocument(); @$dom3->loadHTML( $_td2_h ); $z = new DOMXPath( $dom3 );
				$_tmp2 = array();
				foreach( $z->query('//span/a') as $node3 ) {
					$_tmp2[] = $node3->nodeValue;
				}
				//echo "<pre>"; print_r( $_tmp2 ); echo "</pre>";
				$_val = implode( ",", $_tmp2 );
			}

			if ( $_key == "UserRating" ) {
				$_val = str_replace( array( "(", ")" ), "", $_val );
			}

			$_tmp[ $_key ] = trim( $_val );
		}

		//echo "<pre>"; print_r( $_tmp ); echo "</pre>";

		$film['name'] = strip_tags( $_tmp['ID'] );
		$film['genres'] = strip_tags( $_tmp['Genres'] );
		$film['productionfirm'] = strip_tags( $_tmp['Label'] );
		$film['writers'] = strip_tags( $_tmp['Maker'] );
		$film['vision'] = strip_tags( $_tmp['ReleaseDate'] );
		$film['runtime'] = strip_tags( $_tmp['Length'] );
		$film['director'] = strip_tags( $_tmp['Director'] );
		$film['actors'] = strip_tags( $_tmp['Cast'] );
		$film['ratinga'] = strip_tags( $_tmp['UserRating'] );
		$film['ratingb'] = "10.0";
		$film['ratingc'] = "";
		$film['img'] = $this->cleanWords( $x->query('//img[@id="video_jacket_img"]')->item(0)->getAttribute('src') );

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
print_r( $f->get( "http://www.javlibrary.com/en/?v=javlilyfye" ) );
print_r( $f->get( "http://www.javlibrary.com/en/?v=javlil3zta" ) );
print_r( $f->get( "http://www.javlibrary.com/en/?v=javlilzdsa" ) );
print_r( $f->get( "http://www.javlibrary.com/en/?v=javlilzd74" ) );
print_r( $f->get( "http://www.javlibrary.com/en/?v=javlilzdsi" ) );
echo "</pre>";
*/

?>
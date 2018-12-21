<?php
/*
=====================================================
 Author : Mehmet Hanoğlu <dle.net.tr>
-----------------------------------------------------
 License : MIT License
-----------------------------------------------------
 Copyright (c)
-----------------------------------------------------
 Date : 02.10.2016 [1.0]
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

		preg_match("#\s\(([0-9]+)\)#is", $html, $_tmp2 );
		$film['year'] = trim( $_tmp2[1] );

		preg_match( "#<script type=\"application\/ld\+json\">(.+?)<\/script>#is", $html, $matches );
		$_tmp = json_decode( $matches[1], true );
		$_tmp = array_map( array( $this, "cleanWords" ), $_tmp );

		$film['name'] = $_tmp['name'];
		$film['imdb'] = $_tmp['sameAs'];
		$film['url'] = $_tmp['url'];
		$film['story'] = $_tmp['description'];
		$film['vision'] = $_tmp['releasedEvent']['startDate'];

		$_tmp2 = array();
		foreach( $_tmp['director'] as $director ) { $_tmp2[] = $director['name']; }
		$film['director'] = implode( ",", $_tmp2 ); $_tmp2 = array();

		foreach( $_tmp['actor'] as $director ) { $_tmp2[] = $director['name']; }
		$film['actors'] = implode( ",", $_tmp2 ); $_tmp2 = array();

		$film['img'] = $_tmp['image']['url'];
		$film['genres'] = implode( ", ", $_tmp['genre'] );
		$film['ratinga'] = $_tmp['aggregateRating']['ratingValue'];
		$film['ratingb'] = $_tmp['aggregateRating']['bestRating'];
		$film['ratingc'] = $_tmp['aggregateRating']['ratingCount'];

		preg_match_all( "#<div class=\"float_container_1\">(.+?)</div>#is", $html, $matches );

		$_tmp2 = array();
		foreach( $matches[1] as $m ) {

			preg_match( "#\<h3\>(.+?)\<\/h3\>#is", $m, $_tmp3 );
			$_key = trim( $this->cleanWords( strip_tags( $_tmp3[0] ) ) );
			if ( strpos( $m, "<a" ) !== false ) {
				preg_match( "#href=\"(.+?)\"#is", $m, $_tmp4 );
				$_val = trim( $this->cleanWords( $_tmp4[1] ) );
			} else {
				preg_match( "#\<span\>(.+?)\<\/span\>#is", $m, $_tmp4 );
				$_val = trim( $this->cleanWords( strip_tags( $_tmp4[1] ) ) );
			}
			$_tmp2[ $_key ] = $_val;
		}

		if ( array_key_exists( 'Site oficial', $_tmp2 ) ) $film['official_site'] = $_tmp2['Site oficial'];
		if ( array_key_exists( 'Produs de', $_tmp2 ) ) $film['productionfirm'] = $_tmp2['Produs de'];
		if ( array_key_exists( 'Distribuitorul international', $_tmp2 ) ) $film['distributor'] = $_tmp2['Distribuitorul international'];
		if ( array_key_exists( 'Distribuit in Romania de', $_tmp2 ) ) $film['distributor_ro'] = $_tmp2['Distribuit in Romania de'];
		if ( array_key_exists( 'Ţara', $_tmp2 ) ) $film['country'] = $_tmp2['Ţara'];
		$_tmp2 = array();

		foreach( $x->query('//ul[@class="list1"]/li') as $node ) {
			$_li = $dom->saveHTML( $node );
			$_li_h = "<!DOCTYPE html><html><head>" . $meta . "</head><body>" . $_li . "</body></html>";
			$dom2 = new DOMDocument(); @$dom2->loadHTML( $_li_h ); $y = new DOMXPath( $dom2 );
			$_key = trim( $this->cleanWords( $y->query('//h3')->item(0)->nodeValue ) );
			$_val = trim( $this->cleanWords( $y->query('//span')->item(0)->nodeValue ) );
			$_tmp2[ $_key ] = $_val;
		}

		if ( array_key_exists( 'Durata', $_tmp2 ) ) $film['runtime'] = $_tmp2['Durata'];
		if ( array_key_exists( 'Premiera in Romania', $_tmp2 ) ) $film['vision'] = $_tmp2['Premiera in Romania'];

		$film['screens'] = array();
		foreach( $x->query('//div[@id="movieSmallImageGallery"]/div[@class="img_container"]/a/img') as $node ) {
			$_thumb = $node->getAttribute('src');
			$_org = str_replace(
				array( "resize/", 	"-thumbnail_gallery" ),
				array( "", 			"" ),
				$_thumb
			);
			$film['screens'][] = $_org;
		}

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
print_r( $f->get( "http://www.cinemagia.ro/filme/the-matrix-matrix-209/" ) );
print_r( $f->get( "http://www.cinemagia.ro/filme/miss-peregrines-home-for-peculiars-589636/" ) );
print_r( $f->get( "http://www.cinemagia.ro/filme/deepwater-horizon-579823/" ) );
print_r( $f->get( "http://www.cinemagia.ro/filme/the-revenant-590899/" ) );
print_r( $f->get( "http://www.cinemagia.ro/filme/carol-580614/" ) );
print_r( $f->get( "http://www.cinemagia.ro/filme/blgen-602105/" ) );
echo "</pre>";

*/
?>
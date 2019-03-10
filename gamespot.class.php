<?php
/*
=====================================================
 Author : Mehmet Hanoğlu <dle.net.tr>
-----------------------------------------------------
 License : MIT License
-----------------------------------------------------
 Copyright (c) 2014
-----------------------------------------------------
 Date : 06.06.2015 [1.0]
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
		$html = $this->getURLContent( $url );

		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		$x = new DOMXPath( $dom );

		foreach($x->query("//meta") as $node) {
			$_tmp['meta'][$node->getAttribute("property")] = $node->getAttribute("content");
		}
		preg_match( "/(.*?)\s\(([0-9]+)\)/", $_tmp['meta']['og:title'], $name );
		$film = array(
			'img'			=> $_tmp['meta']['og:image'],
			'name'			=> $_tmp['meta']['og:title'],
			'url'			=> $_tmp['meta']['og:url'],
		);

		$film['publishdate'] = $this->cleanWords( $x->query('//span[@itemprop="datePublished"]')->item(0)->nodeValue );
		if ( preg_match( "#([0-9]{4})#is", $film['publishdate'] , $_tmp ) ) {
			$film['year'] = $_tmp[1];
		}
		$_tmp = array(); foreach( $x->query('//span[@itemprop="device"]/text()') as $node ) { $_tmp[] = $this->cleanWords( $node->nodeValue ); }
		$film['devices'] = implode( ", ", $_tmp );
		$film['ratinga'] = $this->cleanWords( $x->query('//span[@itemprop="ratingValue"]')->item(0)->nodeValue );
		if ( $x->query('//meta[@itemprop="bestRating"]')->item(0) ) $film['ratingb'] = $this->cleanWords( $x->query('//meta[@itemprop="bestRating"]')->item(0)->getAttribute( 'content' ) );
		if ( $x->query('//meta[@itemprop="worstRating"]')->item(0) ) $film['ratingw'] = $this->cleanWords( $x->query('//meta[@itemprop="worstRating"]')->item(0)->getAttribute( 'content' ) );
		if ( $x->query('//dd[@class="pod-objectStats-info__deck"]/text()')->item(0) ) $film['story'] = $this->cleanWords( $x->query('//dd[@class="pod-objectStats-info__deck"]/text()')->item(0)->nodeValue );
		$film['esrb'] = $this->cleanWords( $x->query('//div[@class="pod-objectStats__esrb"]/dl/dt')->item(0)->nodeValue );
		$film['platforms'] = $this->cleanWords( $x->query('//div[@class="pod-objectStats__esrb"]/dl/dd')->item(0)->nodeValue );
		$film['esrb_text'] = $this->cleanWords( $x->query('//div[@class="pod-objectStats__esrb"]/dl/dd')->item(1)->nodeValue );

		preg_match( "#<dl class=\"pod-objectStats-additional\">(.+?)<\/dl>#is", $html, $details );
		if ( isset( $details[0] ) && ! empty( $details[0] ) ) {
			preg_match_all( "#<dt>(.+?)<\/dt>#is", $details[0], $dts );
			$dts = array_map( array( &$this, "clean" ), $dts[1] );
			preg_match_all( "#<dd>(.+?)<\/dd>#is", $details[0], $dds );
			$dds = array_map( "strip_tags", $dds[1] );
			if ( count( $dts ) == count( $dds ) ) {
				$_tmp = array_combine( $dts, $dds );
				$film['publisher'] = trim( $_tmp[ 'Published_by' ] );
				$film['developer'] = trim( $_tmp[ 'Developed_by' ] );
				$film['genres'] = trim( $_tmp[ 'Genre(s)' ] );
				$film['theme'] = trim( $_tmp[ 'Theme(s)' ] );
			}
		}

		if ( $x->query('//a[contains(@data-event-tracking, "Tracking|games_overview|Kubrick|GSReviewScore")]')->item(0) ) {
			$_tmp = $this->cleanWords( $x->query('//a[contains(@data-event-tracking, "Tracking|games_overview|Kubrick|GSReviewScore")]')->item(0)->getAttribute( 'href' ) );
			if ( isset( $_tmp ) && ! empty( $_tmp ) ) {
				if ( preg_match( "#\-([0-9]+)\/#is", $_tmp, $matches ) ) {
					$film['review'] = "http://www.gamespot.com/videos/embed/" . trim( $matches[1] ) . "/";
				}
			}
		}

		foreach( $x->query('//a[contains(@data-event-tracking, "Tracking|games_overview|Images|")]') as $node ) {
			$_tmp = $this->cleanWords( $node->getAttribute( 'href' ) );
			if ( substr( $_tmp, 0, 7 ) == "http://" ) {
				$film['screens'][] = $_tmp;
			}
		}

		return $film;
	}

	private function clean( $text ) {
		return trim( str_replace( array( ":", " "), array( "", "_"), $text ) );
	}


	private function cleanWords( $text ) {
		$text = trim( str_replace( array( "\t", "  ", "\"", "\r\n", "\n", "»", "&#187;", "&raquo;", "See more" ), "", $text ) );
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

/*
$f = new FilmReader();
echo "<pre>";
print_r( $f->get( "http://www.gamespot.com/the-walking-dead-survival-instinct/" ) );
echo "</pre>";
*/

?>
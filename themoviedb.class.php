<?php
/*
=====================================================
 Author : Mehmet Hanoğlu <dle.net.tr>
-----------------------------------------------------
 License : MIT License
-----------------------------------------------------
 Copyright (c)
-----------------------------------------------------
 Date : 08.02.2018 [1.3]
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

		$_tmp = $this->cleanWords( $x->query('//img[@class="poster"]')->item(0)->getAttribute('srcset') );
		$_tmp = explode( " ", $_tmp );
		$film['img'] = $_tmp[0];
		if ( count( $_tmp ) > 2 ) {
			$film['orgimg'] = $_tmp[2];
		}

		//$film['name'] = $this->cleanWords( $x->query('//div[@class="title"]/h2/a')->item(0)->nodeValue );
		$film['name'] = $this->cleanWords( $x->query('//meta[@property="og:title"]')->item(0)->getAttribute('content') );

		$film['year'] = trim( $this->cleanWords( $x->query('//span[@class="release_date"]')->item(0)->nodeValue ), "()" );

		$film['story'] = $this->cleanWords( $x->query('//div[@class="overview"]/p')->item(0)->nodeValue );

		$_tmp = array();
		foreach( $x->query('//li[@class="profile"]') as $node ) {
			$_li = $dom->saveHTML( $node );
			$_li_h = "<!DOCTYPE html><html><head>" . $meta . "</head><body>" . $_li . "</body></html>";
			$dom2 = new DOMDocument(); @$dom2->loadHTML( $_li_h ); $y = new DOMXPath( $dom2 );
			$p1 = $this->cleanWords( $y->query('//p[1]/a')->item(0)->nodeValue );
			$p2 = $this->cleanWords( $y->query('//p[2]')->item(0)->nodeValue );
			if ( strpos( $p2, "," ) !== false ) {
				$_tmp2 = explode( ",", $p2 );
				foreach ( $_tmp2 as $key ) {
					$_tmp[ trim( $key ) ][] = $p1;
				}
			} else {
				$_tmp[ $p2 ][] = $p1;
			}
		}

		$film['director'] = implode( ",", $_tmp['Director'] );
		$film['writers'] = implode( ",", $_tmp['Story'] );
		$film['actors'] = implode( ",", $_tmp['Characters'] );

		$_tmp = array();
		foreach( $x->query('//ol[@class="people scroller"]/li/p/a[contains(@href,"/person/")]') as $node ) {
			//$_li = $dom->saveHTML( $node );
			//$_li_h = "<!DOCTYPE html><html><head>" . $meta . "</head><body>" . $_li . "</body></html>";
			//$dom2 = new DOMDocument(); @$dom2->loadHTML( $_li_h ); $y = new DOMXPath( $dom2 );
			//$p1 = $this->cleanWords( $y->query('//p[1]/a')->item(0)->nodeValue );
			$_tmp[] = $this->cleanWords( $node->nodeValue );
		}
		$film['actors'] = implode( ",", $_tmp );

		preg_match_all( "#<p><strong><bdi>(.+?)</bdi></strong>(.+?)</p>#is", $html, $matches );
		$_tmp = array_combine( $matches[1], $matches[2] );

		$film['status'] = $this->cleanWords( $_tmp['Status'] );
		$film['runtime'] = $this->cleanWords( $_tmp['Runtime'] );
		$film['budget'] = $this->cleanWords( $_tmp['Budget'] );
		$film['language'] = $this->cleanWords( $_tmp['Original Language'] );

		$_tmp = array();
		foreach( $x->query('//li/a[contains(@href,"/genre/")]') as $node ) {
			$_tmp[] = $this->cleanWords( $node->nodeValue );
		}
		$film['genres'] = implode( ",", $_tmp );

		$_tmp = $x->query('//section[@class="facts left_column"]/ul/li')->item(0);
		$_li = $dom->saveHTML( $_tmp );
		preg_match( "#\"\>\s*(.+?)\<br\>\<#is", $_li, $matches );
		$film['datelocal'] = $this->cleanWords(  $matches[1] );

		//$film['ratinga'] = $this->cleanWords( $x->query('//span[@class="rating"]')->item(0)->nodeValue );
		$film['ratinga'] = $this->cleanWords( $x->query('//div[@class="user_score_chart"]')->item(0)->getAttribute('data-percent') );
		$film['ratingb'] = "100";
		$film['ratingc'] = "";
		$film['productionfirm'] = "";

		if ( preg_match( "#src=\"/static_cache/flags_v2/24/([A-Z]+)-[0-9A-Z]{64}\.(png|jpg)\"#is", $html, $matches ) ) {
			$film['country'] = $matches[1];
		}

		return $film;
	}

	private function cleanWords( $text ) {
		$text = str_replace( array( "\t", "  ", " -", "\r\n", "\n" ), "", $text );
		return trim( $text );
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
print_r( $f->get( "https://www.themoviedb.org/movie/336843-the-maze-runner-the-death-cure" ) );
print_r( $f->get( "https://www.themoviedb.org/movie/346672-underworld-blood-wars" ) );
print_r( $f->get( "https://www.themoviedb.org/movie/168259-furious-7" ) );
print_r( $f->get( "https://www.themoviedb.org/movie/107846-escape-plan" ) );
print_r( $f->get( "https://www.themoviedb.org/movie/238-the-godfather" ) );
print_r( $f->get( "https://www.themoviedb.org/movie/278-the-shawshank-redemption" ) );
echo "</pre>";
*/

?>
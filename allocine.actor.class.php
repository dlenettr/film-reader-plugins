<?php
/*
=====================================================
 Author : Mehmet Hanoğlu <dle.net.tr>
-----------------------------------------------------
 License : MIT License
-----------------------------------------------------
 Copyright (c)
-----------------------------------------------------
 Date : 18.09.2016 [1.0]
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

		$film['url'] = $this->cleanWords( $url );
		$film['name'] = $x->query('//div[@id="title"]/meta[@itemprop="name"]')->item(0)->getAttribute('content');
		$film['img'] = $x->query('//div[@class="poster"]/span/img[@itemprop="image"]')->item(0)->getAttribute('src');
		$film['orgimg'] = preg_replace( "#cx_([0-9]+)_([0-9]+)#is", "cx_640_640", $film['img'] );
		$film['story'] = $this->cleanWords( $x->query('//div[@class="margin_20b"][contains(.,"Lire la suite")]/text()')->item(0)->nodeValue );

		$_tmp = array();
		foreach( $x->query('//ul[@class="list_item_p2v tab_col_first"]/li') as $node) {
			$_li = $dom->saveHTML( $node );
			$_li_h = "<!DOCTYPE html><html><head>" . $meta . "</head><body>" . $_li . "</body></html>";
			$dom2 = new DOMDocument(); @$dom2->loadHTML( $_li_h ); $y = new DOMXPath( $dom2 );
			$_key = $this->cleanWords( $y->query('//span')->item(0)->nodeValue );
			$_val = $this->cleanWords( $y->query('//div')->item(0)->nodeValue );
			$_tmp[ $_key ] = $_val;
		}

		$alias = array(
			// site text        => xfield name
			'Nationalité' 		=> "national",
			'Naissance' 		=> "born",
			'Nom de naissance'  => "born_name",
			'Métiers' 			=> "arts",
			'Âge' 				=> "age",
		);

		foreach ( $alias as $o_key => $n_key ) {
			if ( $o_key == "Naissance" ) {
				$_tmp2 = explode( "(", $_tmp[ $o_key ] );
				$_tmp[ $o_key ] = $_tmp2[0];
			} else if ( $o_key == "Métiers" ) {
				$_tmp[ $o_key ] = str_replace( "plus", "", $_tmp[ $o_key ], 1 );
			}
			if ( array_key_exists( $o_key, $_tmp ) ) $film[ $n_key ] = $_tmp[ $o_key ];
		}
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
			curl_setopt($ch, CURLOPT_ENCODING, "Windows-1252");
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

print_r( $f->get( "http://www.allocine.fr/personne/fichepersonne_gen_cpersonne=1722.html" ) );
print_r( $f->get( "http://www.allocine.fr/personne/fichepersonne_gen_cpersonne=15634.html" ) );
print_r( $f->get( "http://www.allocine.fr/personne/fichepersonne_gen_cpersonne=12630.html" ) );
print_r( $f->get( "http://www.allocine.fr/personne/fichepersonne_gen_cpersonne=28586.html" ) );
print_r( $f->get( "http://www.allocine.fr/personne/fichepersonne_gen_cpersonne=38755.html" ) );
echo "</pre>";
*/

?>
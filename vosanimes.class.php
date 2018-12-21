<?php
/*
=====================================================
 Author : Mehmet Hanoğlu <dle.net.tr>
-----------------------------------------------------
 License : MIT License
-----------------------------------------------------
 Copyright (c) 2014
-----------------------------------------------------
 Date : 20.08.2014 [1.0]
=====================================================
*/

class FilmReader {

	public function get( $url ) {
		$html = $this->getURLContent( $url );

		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		$x = new DOMXPath( $dom );

		$_tmps = array(); $_tmp = array(); $count = 0;
		foreach($x->query('//div[@class="info_animes"]/strong') as $node) { $count++; $_tmp[ $count ]['key'] = $this->cleanWords( $node->nodeValue ); } $count = 0;
		foreach($x->query('//div[@class="info_animes"]/font') as $node) { $count++; $_tmp[ $count ]['val'] = $this->cleanWords( $node->nodeValue ); }
		foreach( $_tmp as $a ) { $_tmps[ $a['key'] ] = $a['val']; }
		$film = array(
			'genre'			=> $_tmps['Genre'],
			'name'			=> $_tmps['Titre Original'],
			'productionfirm'=> $_tmps['Studios'],
			'runtime'		=> $_tmps['Durée'],
			'year'			=> $_tmps['Année de production'],
			'language'		=> $_tmps['Langue'],
			'writers'		=> $_tmps['Auteur'],
			'director'		=> $_tmps['Réalisateur'],
			'url'			=> $url
		);
		$film['story'] = $this->cleanWords( $x->query('//div[@class="info_animes2"]')->item(0)->nodeValue );
		$film['img'] = $this->cleanWords( $x->query('//li[@class="movie_teaser2_poster"]/img')->item(0)->getAttribute("src") );
		$more = array(
			'status'	=> $_tmps['Statut'],
			'season'	=> $_tmps['Saison'],
			'episodes'	=> $_tmps['Épisodes'],
		);
		$streams = array();
		foreach( $x->query('//select[@name="select_items"]/option') as $node ) { $streams[] = $this->cleanWords( $node->getAttribute('value') ) . "," . $this->cleanWords( $node->nodeValue ); }
		$_tmp = array(); $count = 0;
		foreach($x->query('//td[@class="title"]/h5/font') as $node) { $_tmp[ $count ]['key'] = $this->cleanWords( $node->nodeValue ); $count++; } $count = 0;
		preg_match_all( "#<div class=\"bouton\">(.+?)<\/div>#is", $html, $matches );
		foreach ( $matches[0] as $count => $data ) {
			preg_match_all( "#<a href=\"(.+?)\".*?>(.+?)<\/a>#is", $data, $matches2 );
			$_tmp[ $count ]['links'] = $matches2[1];
			$_tmp[ $count ]['names'] = $matches2[2];
		}
		$links = "";
		foreach ( $_tmp as $data ) {
			$links .= "###" . $data['key'] . "NEWLINE";
			$alter = count( $data['links'] );
			for( $x = 0; $x < $alter; $x++ ) {
				$links .= $data['links'][$x] . "," . $data['names'][$x] . "NEWLINE";
			}
		}

		$film = array_merge( $film, $more, array( 'links' => $links ), array( 'streams' => implode( "NEWLINE", $streams ) ) );
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

?>
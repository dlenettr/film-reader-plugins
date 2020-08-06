<?php
/*
=====================================================
 Author : Mehmet Hanoğlu <dle.net.tr>
-----------------------------------------------------
 License : MIT License
-----------------------------------------------------
 Copyright (c)
-----------------------------------------------------
 Date : 06.08.2020 [1.7]
=====================================================
*/

class FilmReader {

	public function get( $url ) {
		$html = $this->getURLContent( $url );

		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		$x = new DOMXPath( $dom );

		$film = [];
		$film['url'] = $this->cleanWords( $url );
		$_temp_keys = [];
		foreach($x->query('//div[@class="nm-info-l"]') as $node) { $_temp_keys[] = $this->cleanWords( str_replace(":", "", $node->nodeValue ) ); }
		$_temp_values = [];
		foreach($x->query('//div[contains(@class, "nm-info-r")]') as $node) { $_temp_values[] = $this->cleanWords( $node->nodeValue ); }
		$_temp = array_combine( $_temp_keys, $_temp_values );
		unset( $_temp_keys, $_temp_values );
		$film['director'] = $_temp['Yönetmen'];
		$film['country'] = $_temp['Ülke'];
		$film['datelocal'] = $_temp['Vizyon Tarihi'];
		$film['runtime'] = $x->query('//span[@itemprop="duration"]')->item(0)->nodeValue;
		$film['genres'] = str_replace( " |", ",", $_temp['Tür'] );
		$film['soundtracks'] = $_temp['Müzik'];
		$film['writers'] = $_temp['Senaryo'];
		$film['language'] = $_temp['Dil'];
		$film['locations'] = $_temp['Çekim Yeri'];
		$film['budget'] = $_temp['Bütçe'];
		$film['productionfirm'] = str_replace( " |", ",", $_temp['Firma'] );
		$film['author'] = $_temp['Yapımcı'];
		$film['screenman'] = $_temp['Görüntü Yönetmeni'];
		$film['ratinga'] = $this->cleanWords( $x->query('//span[@itemprop="ratingValue"]')->item(0)->nodeValue );
		//$film['ratinga'] = strstr($_temp['Rating'], ' ', true); // IMDB Ratig
		$film['year'] = trim($this->cleanWords( $x->query('//span[@class="year"]')->item(0)->nodeValue ), '()');
		$film['namelocal'] = $_temp['Nam-ı Diğer'];
		$film['name'] = $this->cleanWords( $x->query('//h1/span[@itemprop="name"]')->item(0)->nodeValue );
		$film['namelong'] = $film['name'] . " (" . $film['year'] . ")";
		$film['ratingc'] = $this->cleanWords( $x->query('//span[@itemprop="ratingCount"]')->item(0)->nodeValue );
		$film['ratingb'] = "10";
		$film['story'] = $this->cleanWords( $x->query('//div[@class="ozet-goster"]')->item(0)->nodeValue );
		foreach($x->query('//input[@type="hidden"]') as $node) { $_temp[$node->getAttribute("name")] = $this->cleanWords( $node->getAttribute("value") ); }
		$_temp = [];
		foreach($x->query('//a/img') as $node) { $_temp[] = $this->cleanWords( $node->getAttribute("src") ); }
		$film['img'] = "http://www.turkcealtyazi.org" . $_temp[0]; $_temp = [];
		foreach($x->query('//div[@itemprop="actors"]/ul/li/a/span') as $node) { $_temp[] = $this->cleanWords( $node->nodeValue ); }
		$film['actors'] = implode(", ", $_temp);
		unset( $_temp );

		return $film;
	}

	private function cleanWords( $text ) {
		$text = str_replace( array( "\t", "  ", "\r\n", "\n", "tümü", "»", "&#187;", "&raquo;" ), "", $text );
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
			$output = curl_exec( $ch );
			curl_close( $ch );
		} else {
			$output = file_get_contents($url);
		}
		return $output;
	}
}

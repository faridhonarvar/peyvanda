<?php
/**
 * Dev tool (not shipped): builds languages/peyvanda.pot, merges it into each .po,
 * then compiles .mo files and JSON files for the editor scripts.
 *
 * Usage: php bin/i18n.php
 *
 * @package Peyvanda
 */

// phpcs:ignoreFile -- CLI developer tool, not part of the plugin.

$root = dirname( __DIR__ );
$lang = $root . '/languages';

$entries = array(); // key => [ ctx, singular, plural, refs[], comments[] ].

function peyvanda_i18n_add( &$entries, $ctx, $singular, $plural, $ref, $comment = '' ) {
	$key = $ctx . "\4" . $singular;
	if ( ! isset( $entries[ $key ] ) ) {
		$entries[ $key ] = array( 'ctx' => $ctx, 'singular' => $singular, 'plural' => $plural, 'refs' => array(), 'comments' => array() );
	}
	$entries[ $key ]['refs'][] = $ref;
	if ( '' !== $comment && ! in_array( $comment, $entries[ $key ]['comments'], true ) ) {
		$entries[ $key ]['comments'][] = $comment;
	}
}

function peyvanda_i18n_files( $root, $ext ) {
	$out = array();
	$it  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $it as $file ) {
		$path = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $root ) + 1 ) );
		if ( preg_match( '#^(vendor|node_modules|tests|bin|\.git)/#', $path ) || pathinfo( $path, PATHINFO_EXTENSION ) !== $ext ) {
			continue;
		}
		$out[] = $path;
	}
	sort( $out );
	return $out;
}

// PHP: token based.
$functions = array(
	'__'          => array( 's', 'd' ),
	'_e'          => array( 's', 'd' ),
	'esc_html__'  => array( 's', 'd' ),
	'esc_html_e'  => array( 's', 'd' ),
	'esc_attr__'  => array( 's', 'd' ),
	'esc_attr_e'  => array( 's', 'd' ),
	'_x'          => array( 's', 'c', 'd' ),
	'esc_html_x'  => array( 's', 'c', 'd' ),
	'esc_attr_x'  => array( 's', 'c', 'd' ),
	'_n'          => array( 's', 'p', 'n', 'd' ),
	'_nx'         => array( 's', 'p', 'n', 'c', 'd' ),
);

foreach ( peyvanda_i18n_files( $root, 'php' ) as $path ) {
	$tokens  = token_get_all( file_get_contents( $root . '/' . $path ) );
	$comment = '';
	$count   = count( $tokens );
	for ( $i = 0; $i < $count; $i++ ) {
		$t = $tokens[ $i ];
		if ( is_array( $t ) && T_COMMENT === $t[0] && false !== stripos( $t[1], 'translators:' ) ) {
			$comment = trim( preg_replace( '#^\s*(/\*+|//)\s*|\s*\*+/\s*$#', '', $t[1] ) );
			continue;
		}
		if ( ! is_array( $t ) || T_STRING !== $t[0] || ! isset( $functions[ $t[1] ] ) ) {
			continue;
		}
		$j = $i + 1;
		while ( $j < $count && is_array( $tokens[ $j ] ) && T_WHITESPACE === $tokens[ $j ][0] ) {
			$j++;
		}
		if ( '(' !== $tokens[ $j ] ) {
			continue;
		}
		$args  = array();
		$depth = 0;
		$cur   = null;
		for ( $k = $j + 1; $k < $count; $k++ ) {
			$tk = $tokens[ $k ];
			if ( '(' === $tk || '[' === $tk ) {
				$depth++;
			} elseif ( ')' === $tk || ']' === $tk ) {
				if ( 0 === $depth ) {
					$args[] = $cur;
					break;
				}
				$depth--;
			} elseif ( ',' === $tk && 0 === $depth ) {
				$args[] = $cur;
				$cur    = null;
				continue;
			}
			if ( 0 === $depth && is_array( $tk ) && T_CONSTANT_ENCAPSED_STRING === $tk[0] && null === $cur ) {
				$cur = stripcslashes( substr( $tk[1], 1, -1 ) );
			} elseif ( is_array( $tk ) && T_WHITESPACE === $tk[0] ) {
				continue;
			} elseif ( null === $cur || ( is_array( $tk ) && T_CONSTANT_ENCAPSED_STRING !== $tk[0] ) ) {
				$cur = false === $cur ? false : ( null === $cur ? false : $cur );
			}
		}
		$spec = $functions[ $t[1] ];
		$vals = array();
		foreach ( $spec as $n => $role ) {
			$vals[ $role ] = isset( $args[ $n ] ) ? $args[ $n ] : null;
		}
		if ( 'peyvanda' !== $vals['d'] || ! is_string( $vals['s'] ) ) {
			continue;
		}
		peyvanda_i18n_add( $entries, isset( $vals['c'] ) && is_string( $vals['c'] ) ? $vals['c'] : '', $vals['s'], isset( $vals['p'] ) && is_string( $vals['p'] ) ? $vals['p'] : '', $path . ':' . $t[2], $comment );
		$comment = '';
	}
}

// Plugin header.
$header = file_get_contents( $root . '/peyvanda.php' );
foreach ( array( 'Plugin Name' => 'Name of the plugin', 'Description' => 'Description of the plugin', 'Author' => 'Author of the plugin' ) as $field => $note ) {
	if ( preg_match( '/^\s*\*\s*' . $field . ':\s*(.+)$/m', $header, $m ) ) {
		peyvanda_i18n_add( $entries, '', trim( $m[1] ), '', 'peyvanda.php', $note );
	}
}

// block.json files.
foreach ( peyvanda_i18n_files( $root, 'json' ) as $path ) {
	if ( 'block.json' !== basename( $path ) ) {
		continue;
	}
	$json = json_decode( file_get_contents( $root . '/' . $path ), true );
	if ( ! empty( $json['title'] ) ) {
		peyvanda_i18n_add( $entries, 'block title', $json['title'], '', $path );
	}
	if ( ! empty( $json['description'] ) ) {
		peyvanda_i18n_add( $entries, 'block description', $json['description'], '', $path );
	}
	foreach ( (array) ( $json['keywords'] ?? array() ) as $keyword ) {
		peyvanda_i18n_add( $entries, 'block keyword', $keyword, '', $path );
	}
}

// JS: __( 'text', 'peyvanda' ) with single quotes.
$js_strings = array(); // path => keys.
foreach ( peyvanda_i18n_files( $root, 'js' ) as $path ) {
	$src = file_get_contents( $root . '/' . $path );
	if ( preg_match_all( "/\\b__\\(\\s*'((?:[^'\\\\]|\\\\.)*)'\\s*,\\s*'peyvanda'\\s*\\)/", $src, $m, PREG_OFFSET_CAPTURE ) ) {
		foreach ( $m[1] as $match ) {
			$text = stripcslashes( $match[0] );
			$line = substr_count( substr( $src, 0, $match[1] ), "\n" ) + 1;
			peyvanda_i18n_add( $entries, '', $text, '', $path . ':' . $line );
			$js_strings[ $path ][] = "\4" . $text;
		}
	}
}

function peyvanda_po_str( $s ) {
	return '"' . addcslashes( $s, "\\\"\n\t" ) . '"';
}

// Write POT.
$pot  = "# Copyright (C) Farid Honarvar\n# This file is distributed under the GPL-2.0-or-later.\nmsgid \"\"\nmsgstr \"\"\n";
$pot .= "\"Project-Id-Version: Peyvanda 0.1.3\\n\"\n\"MIME-Version: 1.0\\n\"\n\"Content-Type: text/plain; charset=UTF-8\\n\"\n\"Content-Transfer-Encoding: 8bit\\n\"\n\"X-Domain: peyvanda\\n\"\n";
foreach ( $entries as $e ) {
	$pot .= "\n";
	foreach ( $e['comments'] as $c ) {
		$pot .= '#. ' . $c . "\n";
	}
	$pot .= '#: ' . implode( ' ', array_unique( $e['refs'] ) ) . "\n";
	if ( false !== strpos( $e['singular'], '%' ) ) {
		$pot .= "#, php-format\n";
	}
	if ( '' !== $e['ctx'] ) {
		$pot .= 'msgctxt ' . peyvanda_po_str( $e['ctx'] ) . "\n";
	}
	$pot .= 'msgid ' . peyvanda_po_str( $e['singular'] ) . "\n";
	if ( '' !== $e['plural'] ) {
		$pot .= 'msgid_plural ' . peyvanda_po_str( $e['plural'] ) . "\nmsgstr[0] \"\"\nmsgstr[1] \"\"\n";
	} else {
		$pot .= "msgstr \"\"\n";
	}
}
file_put_contents( $lang . '/peyvanda.pot', $pot );
echo count( $entries ) . " strings in peyvanda.pot\n";

// Parse a PO file into key => [ msgstr... ] plus header.
function peyvanda_parse_po( $file ) {
	$out   = array();
	$lines = file( $file, FILE_IGNORE_NEW_LINES );
	$cur   = array();
	$field = null;
	$flush = function () use ( &$cur, &$out ) {
		if ( isset( $cur['msgid'] ) ) {
			$key         = ( $cur['msgctxt'] ?? '' ) . "\4" . $cur['msgid'];
			$out[ $key ] = $cur;
		}
		$cur = array();
	};
	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			$flush();
			$field = null;
			continue;
		}
		if ( '#' === $line[0] ) {
			continue;
		}
		if ( preg_match( '/^(msgctxt|msgid_plural|msgid|msgstr(?:\[(\d+)\])?)\s+"(.*)"$/', $line, $m ) ) {
			if ( 'msgid' === $m[1] && isset( $cur['msgid'] ) ) {
				$flush();
			}
			$field         = $m[1];
			$cur[ $field ] = stripcslashes( $m[3] );
		} elseif ( preg_match( '/^"(.*)"$/', $line, $m ) && $field ) {
			$cur[ $field ] .= stripcslashes( $m[1] );
		}
	}
	$flush();
	return $out;
}

foreach ( glob( $lang . '/peyvanda-*.po' ) as $po_file ) {
	$locale = substr( basename( $po_file, '.po' ), strlen( 'peyvanda-' ) );
	$po     = peyvanda_parse_po( $po_file );
	$header = $po[ "\4" ]['msgstr'] ?? '';

	// Merge: rewrite the PO in POT order, keeping translations.
	$merged  = "# Persian translation of Peyvanda.\n# This file is distributed under the GPL-2.0-or-later.\nmsgid \"\"\nmsgstr \"\"\n";
	foreach ( explode( "\n", rtrim( $header, "\n" ) ) as $h ) {
		$merged .= peyvanda_po_str( $h . "\n" ) . "\n";
	}
	$missing = array();
	$table   = array(); // For MO/JSON.
	foreach ( $entries as $key => $e ) {
		$t       = $po[ $key ] ?? array();
		$merged .= "\n";
		foreach ( $e['comments'] as $c ) {
			$merged .= '#. ' . $c . "\n";
		}
		$merged .= '#: ' . implode( ' ', array_unique( $e['refs'] ) ) . "\n";
		if ( '' !== $e['ctx'] ) {
			$merged .= 'msgctxt ' . peyvanda_po_str( $e['ctx'] ) . "\n";
		}
		$merged .= 'msgid ' . peyvanda_po_str( $e['singular'] ) . "\n";
		if ( '' !== $e['plural'] ) {
			$s0      = $t['msgstr[0]'] ?? '';
			$s1      = $t['msgstr[1]'] ?? $s0;
			$merged .= 'msgid_plural ' . peyvanda_po_str( $e['plural'] ) . "\nmsgstr[0] " . peyvanda_po_str( $s0 ) . "\nmsgstr[1] " . peyvanda_po_str( $s1 ) . "\n";
			if ( '' !== $s0 ) {
				$table[ $key ] = array( 'plural' => $e['plural'], 'strs' => array( $s0, $s1 ) );
			} else {
				$missing[] = $e['singular'];
			}
		} else {
			$s       = $t['msgstr'] ?? '';
			$merged .= 'msgstr ' . peyvanda_po_str( $s ) . "\n";
			if ( '' !== $s ) {
				$table[ $key ] = array( 'plural' => '', 'strs' => array( $s ) );
			} else {
				$missing[] = $e['singular'];
			}
		}
	}
	file_put_contents( $po_file, $merged );

	// MO.
	$mo_entries = array( '' => $header );
	foreach ( $table as $key => $row ) {
		list( $ctx, $id ) = explode( "\4", $key, 2 );
		$orig             = ( '' !== $ctx ? $ctx . "\4" : '' ) . $id . ( '' !== $row['plural'] ? "\0" . $row['plural'] : '' );
		$mo_entries[ $orig ] = implode( "\0", $row['strs'] );
	}
	ksort( $mo_entries, SORT_STRING );
	$n      = count( $mo_entries );
	$o_tab  = 28;
	$t_tab  = $o_tab + 8 * $n;
	$offset = $t_tab + 8 * $n;
	$origs  = '';
	$trans  = '';
	$o_meta = array();
	$t_meta = array();
	foreach ( $mo_entries as $o => $t ) {
		$o_meta[] = array( strlen( $o ), strlen( $origs ) );
		$origs   .= $o . "\0";
	}
	foreach ( $mo_entries as $o => $t ) {
		$t_meta[] = array( strlen( $t ), strlen( $trans ) );
		$trans   .= $t . "\0";
	}
	$mo = pack( 'V7', 0x950412de, 0, $n, $o_tab, $t_tab, 0, $offset );
	foreach ( $o_meta as $m ) {
		$mo .= pack( 'VV', $m[0], $offset + $m[1] );
	}
	$t_start = $offset + strlen( $origs );
	foreach ( $t_meta as $m ) {
		$mo .= pack( 'VV', $m[0], $t_start + $m[1] );
	}
	$mo .= $origs . $trans;
	file_put_contents( $lang . '/peyvanda-' . $locale . '.mo', $mo );

	// JSON for each editor script.
	foreach ( glob( $lang . '/peyvanda-' . $locale . '-*.json' ) as $old ) {
		unlink( $old );
	}
	foreach ( $js_strings as $path => $keys ) {
		$messages = array(
			'' => array(
				'domain'       => 'messages',
				'lang'         => $locale,
				'plural-forms' => 'nplurals=2; plural=(n > 1);',
			),
		);
		foreach ( array_unique( $keys ) as $key ) {
			if ( isset( $table[ $key ] ) ) {
				$messages[ substr( $key, 1 ) ] = $table[ $key ]['strs'];
			}
		}
		$json = array(
			'translation-revision-date' => gmdate( 'Y-m-d H:i:sO' ),
			'generator'                 => 'peyvanda/bin/i18n.php',
			'source'                    => $path,
			'domain'                    => 'messages',
			'locale_data'               => array( 'messages' => $messages ),
		);
		file_put_contents( $lang . '/peyvanda-' . $locale . '-' . md5( $path ) . '.json', json_encode( $json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . "\n" );
	}

	echo $locale . ': ' . count( $table ) . ' translated, ' . count( $missing ) . " missing\n";
	foreach ( $missing as $m ) {
		echo '  - ' . $m . "\n";
	}
}

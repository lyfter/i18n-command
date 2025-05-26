<?php

namespace WP_CLI\I18n;

class BladeDirectives {

	/**
	 * @var \eftec\bladeone\BladeOne $blade_compiler
	 */
	public static function register( $blade_compiler ) {
		$blade_compiler->directive(
			't',
			[ static::class, 't' ]
		);
	}

	public static function t( string $expression ): string {
		$args = join(
			'',
			array_map(
				function ( $s ) {
					return trim( $s );
				},
				explode( "\n", $expression )
			)
		);

		preg_match( '/([\'"]{1}[^[]*[\'"]{1}),\s?(\[.*\]){0,1},?\s?([\'"\w\-_\'"]*)/', $args, $matches );

		[, $key, $data, $text_domain] = $matches ?: [ null, $args, null, null ];

		if ( ! $data ) {
			return '<?= e(__(' . ( $text_domain ? "{$args}" : "{$args}, 'lyfter'" ) . ')) ?>';
		}

		preg_match_all( '/{-{0,1}\s?[\w\-]*\s?}/', $key, $tag_matches );
		$data_entries = explode( '_SPLIT_', preg_replace( '/,\s?([\'"]{1})/', '_SPLIT_$1', substr( $data, 1, -1 ) ) );
		$data_ar      = [];

		foreach ( $data_entries as $entry ) {
			[$k, $v]                                = explode( '=>', $entry );
			$data_ar[ substr( trim( $k ), 1, -1 ) ] = trim( $v );
		}

		$tags         = '';
		$placeholders = '';
		$replacement  = '';

		foreach ( $tag_matches[0] as $i => $tag ) {
			$tag_key = trim( str_replace( [ '{-', '{', '}' ], [], $tag ) );
			$raw     = substr( $tag, 0, 2 ) === '{-';
			$index   = $i + 1;

			$replacement  .= ( $raw ? "{$data_ar[$tag_key]}" : "e({$data_ar[$tag_key]})" ) . ',';
			$tags         .= "'{$tag}',";
			$placeholders .= "'%{$index}\$s',";
		}

		$replacement = substr( $replacement, 0, -1 );
		$domain      = $text_domain ?: "'lyfter'";

		return "<?= sprintf(str_replace([{$tags}], [{$placeholders}], __({$key}, {$domain})), {$replacement}) ?>";
	}
}

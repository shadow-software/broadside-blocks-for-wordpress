<?php
/**
 * The site editor must know every Broadside block.
 *
 * WordPress 7 does not put a PHP-only block into the editor. blocks.js has to
 * call registerBlockType for each one, once. Seven block.json files used to
 * point editorScript at that same file, so WordPress printed it once per block
 * and the blocks the file never registered — byline, author bio, the single
 * template — showed up as "Your site doesn't include support for this block"
 * while the front end, which only needs the PHP render, looked fine.
 *
 * This fails the build if that shape comes back.
 *
 * Run: php scripts/guard-editor-registration.php
 */

declare( strict_types = 1 );

$root = dirname( __DIR__ );
$fail = 0;

/**
 * @param bool   $ok   Whether the check passed.
 * @param string $what What was checked.
 */
function guard_check( bool $ok, string $what ): void {
	global $fail;

	if ( $ok ) {
		echo "  ✓ {$what}\n";
		return;
	}

	++$fail;
	fwrite( STDERR, "  ✗ {$what}\n" );
}

echo "── every block.json is registered in PHP and in the editor script\n";

$blocks = array();

foreach ( glob( $root . '/blocks/*/block.json' ) ?: array() as $file ) {
	$data = json_decode( (string) file_get_contents( $file ), true );

	if ( ! is_array( $data ) || empty( $data['name'] ) ) {
		guard_check( false, basename( dirname( $file ) ) . '/block.json is not a block' );
		continue;
	}

	$name = (string) $data['name'];
	$blocks[ $name ] = $data;

	guard_check(
		! isset( $data['editorScript'] ),
		"{$name} has no editorScript (the shared file is enqueued once)"
	);
}

$php = (string) file_get_contents( $root . '/inc-blocks.php' )
	. (string) file_get_contents( $root . '/inc-blocks-masthead.php' );
$js  = (string) file_get_contents( $root . '/assets/js/blocks.js' );

preg_match_all( "/'([a-z0-9-]+)'\\s*=>\\s*'broadside_blocks_render_/", $php, $registered );
$php_names = array();

foreach ( $registered[1] as $short ) {
	$php_names[] = 'broadside/' . $short;
}

preg_match_all( "/(?:register|phpBlock)\\(\\s*'([^']+)'/", $js, $js_registered );
$js_names = $js_registered[1];

sort( $php_names );
$json_names = array_keys( $blocks );
sort( $json_names );
$js_sorted = $js_names;
sort( $js_sorted );

guard_check( $json_names === $php_names, 'PHP registers every block.json and nothing else' );
guard_check( $json_names === $js_sorted, 'blocks.js registers every block.json and nothing else' );
guard_check( in_array( 'broadside/lead-promo', $json_names, true ), 'lead-promo exists (the front page template asks for it)' );
guard_check( in_array( 'broadside/byline', $js_names, true ), 'byline is registered in the editor' );
guard_check( in_array( 'broadside/author-bio', $js_names, true ), 'author-bio is registered in the editor' );
guard_check( in_array( 'broadside/standards', $js_names, true ), 'standards is registered in the editor' );
guard_check( in_array( 'broadside/related', $js_names, true ), 'related is registered in the editor' );
guard_check( in_array( 'broadside/newsletter', $js_names, true ), 'newsletter is registered in the editor' );

$grid = $blocks['broadside/section-grid'] ?? array();
guard_check(
	isset( $grid['attributes']['categories']['type'] ) && 'array' === $grid['attributes']['categories']['type'],
	'section-grid has a categories attribute'
);

$bootstrap = (string) file_get_contents( $root . '/broadside-blocks.php' );
guard_check(
	str_contains( $bootstrap, "add_action( 'enqueue_block_editor_assets', 'broadside_blocks_enqueue_editor_script' )" ),
	'the editor script is enqueued once from the plugin bootstrap'
);
guard_check(
	str_contains( $js, 'if ( blocks.getBlockType( name ) )' ),
	'a second copy of the editor script does not register again'
);
guard_check(
	str_contains( $php, "'suppress_filters' => false" ) || str_contains( $php, "['suppress_filters'] = false" ),
	'secondary queries do not suppress Polylang'
);

$masthead = (string) file_get_contents( $root . '/inc-blocks-masthead.php' );
guard_check( str_contains( $masthead, 'digest-footer__podcast-link' ), 'the colophon prints the podcast directory link' );
guard_check( str_contains( $masthead, 'digest-footer__youtube-link' ), 'the colophon prints the YouTube link' );
guard_check( str_contains( $masthead, 'shadow_digest_publisher_legal' ), 'the colophon prints the publisher legal line' );

echo "\n── versions agree\n";

preg_match( '/^\s*\*\s*Version:\s*(\S+)/m', $bootstrap, $header );
preg_match( "/BROADSIDE_BLOCKS_VERSION',\\s*'([^']+)'/", $bootstrap, $const );
$readme = (string) file_get_contents( $root . '/readme.txt' );
preg_match( '/^Stable tag:\s*(\S+)/m', $readme, $stable );
$asset = (string) file_get_contents( $root . '/assets/js/blocks.asset.php' );
preg_match( "/'version'\\s*=>\\s*'([^']+)'/", $asset, $asset_version );

$versions = array(
	'header'    => $header[1] ?? '',
	'constant'  => $const[1] ?? '',
	'readme'    => $stable[1] ?? '',
	'editor js' => $asset_version[1] ?? '',
);

$unique = array_unique( array_values( $versions ) );
guard_check(
	1 === count( $unique ) && '' !== ( $unique[0] ?? '' ),
	'header, constant, readme and blocks.asset.php are ' . implode( ' / ', $versions )
);

echo "\n── loading the editor script twice does not throw\n";

$node = trim( (string) shell_exec( 'command -v node' ) );
guard_check( '' !== $node, 'node is available' );

if ( '' !== $node ) {
	$runner = <<<'JS'
const fs = require('fs');
const code = fs.readFileSync(process.argv[2], 'utf8');
const registered = {};
global.window = { wp: {
  blocks: {
    getBlockType: (name) => registered[name],
    registerBlockType: (name, settings) => {
      if (registered[name]) throw new Error('duplicate ' + name);
      registered[name] = settings;
      return settings;
    },
  },
  element: { createElement() {}, Fragment: 'F', useState: (v) => [v, () => {}], useEffect() {} },
  blockEditor: { useBlockProps: () => ({}), RichText: {}, InspectorControls: {} },
  components: { PanelBody: {}, TextControl: {}, TextareaControl: {}, Button: {}, Notice: {}, CheckboxControl: {} },
  i18n: { __: (s) => s },
  serverSideRender() {},
  apiFetch: () => Promise.resolve([]),
}};
eval(code);
eval(code);
const need = ['broadside/byline','broadside/author-bio','broadside/standards','broadside/related','broadside/newsletter','broadside/lead-promo','broadside/section-grid'];
for (const name of need) {
  if (typeof registered[name]?.edit !== 'function') {
    console.error('missing edit for ' + name);
    process.exit(1);
  }
}
console.log(Object.keys(registered).length);
JS;

	$tmp = tempnam( sys_get_temp_dir(), 'broadside-editor-' );
	file_put_contents( (string) $tmp, $runner );
	$cmd = escapeshellarg( $node ) . ' ' . escapeshellarg( (string) $tmp ) . ' ' . escapeshellarg( $root . '/assets/js/blocks.js' );
	$out = array();
	$code = 0;
	exec( $cmd . ' 2>&1', $out, $code );
	@unlink( (string) $tmp );

	guard_check( 0 === $code, 'second load is silent (' . trim( implode( "\n", $out ) ) . ' blocks)' );
}

echo "\n";

if ( 0 === $fail ) {
	echo "✓ editor registration holds.\n";
	exit( 0 );
}

fwrite( STDERR, "✗ {$fail} editor-registration checks failed.\n" );
exit( 1 );

<?php
/**
 * A design (template + style + schema).
 *
 * @package Peyvanda
 */

namespace Peyvanda\Designs;

defined( 'ABSPATH' ) || exit;

/**
 * Value object describing one design folder.
 */
final class Design {

	/**
	 * Slug.
	 *
	 * @var string
	 */
	public $slug;

	/**
	 * Label.
	 *
	 * @var string
	 */
	public $label;

	/**
	 * Absolute directory path with trailing slash.
	 *
	 * @var string
	 */
	public $dir;

	/**
	 * Directory URL with trailing slash.
	 *
	 * @var string
	 */
	public $url;

	/**
	 * Parsed schema.json.
	 *
	 * @var array<string,mixed>|null
	 */
	private $schema = null;

	/**
	 * Constructor.
	 *
	 * @param string $slug  Slug.
	 * @param string $label Label.
	 * @param string $dir   Directory containing template.php, style.css and schema.json.
	 * @param string $url   URL of that directory.
	 */
	public function __construct( $slug, $label, $dir, $url ) {
		$this->slug  = sanitize_key( $slug );
		$this->label = (string) $label;
		$this->dir   = trailingslashit( $dir );
		$this->url   = trailingslashit( $url );
	}

	/**
	 * Style handle.
	 *
	 * @return string
	 */
	public function handle() {
		return 'peyvanda-design-' . $this->slug;
	}

	/**
	 * Template path.
	 *
	 * @return string
	 */
	public function template() {
		return $this->dir . 'template.php';
	}

	/**
	 * Schema (fields and image size).
	 *
	 * @return array<string,mixed>
	 */
	public function schema() {
		if ( null === $this->schema ) {
			$schema = array();
			$file   = $this->dir . 'schema.json';
			if ( is_readable( $file ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local plugin file.
				$decoded = json_decode( (string) file_get_contents( $file ), true );
				$schema  = is_array( $decoded ) ? $decoded : array();
			}
			$schema['fields'] = isset( $schema['fields'] ) && is_array( $schema['fields'] ) ? $schema['fields'] : array();
			$this->schema     = $schema;
		}
		return $this->schema;
	}

	/**
	 * Editable fields.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function fields() {
		$schema = $this->schema();
		return $schema['fields'];
	}

	/**
	 * Image size used by the template.
	 *
	 * @return string
	 */
	public function image_size() {
		$schema = $this->schema();
		return isset( $schema['image_size'] ) ? (string) $schema['image_size'] : 'thumbnail';
	}

	/**
	 * Values of the fields, sanitized against the schema and merged with defaults.
	 *
	 * @param array<string,mixed> $saved Saved values.
	 * @return array<string,mixed>
	 */
	public function values( array $saved ) {
		$values = array();
		foreach ( $this->fields() as $key => $field ) {
			$default        = isset( $field['default'] ) ? $field['default'] : '';
			$values[ $key ] = self::sanitize_field( $field, array_key_exists( $key, $saved ) ? $saved[ $key ] : $default, $default );
		}
		return $values;
	}

	/**
	 * Sanitizes one field value.
	 *
	 * @param array<string,mixed> $field   Field schema.
	 * @param mixed               $value   Raw value.
	 * @param mixed               $default_value Default.
	 * @return mixed
	 */
	public static function sanitize_field( array $field, $value, $default_value ) {
		$type = isset( $field['type'] ) ? $field['type'] : 'text';
		switch ( $type ) {
			case 'color':
				$color = sanitize_hex_color( is_string( $value ) ? $value : '' );
				return $color ? $color : (string) $default_value;
			case 'number':
				$min = isset( $field['min'] ) ? (float) $field['min'] : 0;
				$max = isset( $field['max'] ) ? (float) $field['max'] : 100;
				if ( ! is_numeric( $value ) ) {
					$value = $default_value;
				}
				return max( $min, min( $max, (int) $value ) );
			case 'toggle':
				return (bool) $value;
			case 'select':
				$options = isset( $field['options'] ) ? array_map( 'strval', (array) $field['options'] ) : array();
				return in_array( (string) $value, $options, true ) ? (string) $value : (string) $default_value;
			default:
				return sanitize_text_field( (string) $value );
		}
	}

	/**
	 * CSS custom properties for the box root (`--pvd-*`).
	 *
	 * @param array<string,mixed> $values Sanitized values.
	 * @return string
	 */
	public function css_variables( array $values ) {
		$css = array();
		foreach ( $this->fields() as $key => $field ) {
			if ( empty( $field['css'] ) || ! isset( $values[ $key ] ) ) {
				continue;
			}
			$var   = '--pvd-' . sanitize_key( (string) $field['css'] );
			$unit  = isset( $field['unit'] ) && 'px' === $field['unit'] ? 'px' : '';
			$value = is_bool( $values[ $key ] ) ? (int) $values[ $key ] : $values[ $key ];
			$css[] = $var . ':' . $value . $unit;
		}
		return implode( ';', $css );
	}
}

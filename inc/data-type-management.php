<?php
/**
 * Adds data type registration capabilities.
 *
 * @package data-types
 */

/**
 * Register the Data Types Manager in the sidebar, as well as the existing data types.
 */
add_action(
	'init',
	function () {
		register_post_type(
			'data_types',
			array(
				'label'        => 'Data Types',
				'public'       => true,
				'show_in_menu' => true,
				'show_in_rest' => true,
			)
		);

		$data_types = get_registered_data_types();

		foreach ( $data_types as $data_type ) {
			$blocks = parse_blocks( $data_type->template );

			register_post_type(
				$data_type->slug,
				array(
					'label'        => $data_type->name,
					'public'       => true,
					'show_in_menu' => true,
					'show_in_rest' => true,
					'icon'         => 'dashicons-admin-site',
					'template'     => _convert_parsed_blocks_for_js( $blocks ),
				)
			);

			$meta_fields = _get_meta_fields( $blocks );

			foreach ( $meta_fields as $meta_field ) {
				register_post_meta(
					$data_type->slug,
					$meta_field,
					array(
						'show_in_rest' => true,
						'single'       => true,
						'type'         => 'string',
						'default'      => $meta_field,
					)
				);
			}
		}
	},
	0
);

/**
 * Converts parsed blocks to a format Gutenberg can understand.
 *
 * @param array $blocks A list of blocks.
 */
function _convert_parsed_blocks_for_js( $blocks ) {
	$template = array();
	foreach ( $blocks as $block ) {
		if ( null === $block['blockName'] && empty( trim( $block['innerHTML'] ) ) ) {
			continue;
		}

		$entry = array( $block['blockName'], $block['attrs'] );
		if ( isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
			$entry[] = _convert_parsed_blocks_for_js( $block['innerBlocks'] );
		}
		$template[] = $entry;
	}
	return $template;
}

/**
 * Parse the blocks looking for bound post meta fields.
 *
 * TODO: Fix recursion.
 *
 * @param array $blocks The blocks from the CPT template.
 */
function _get_meta_fields( $blocks ) {
	$meta_fields = array();

	foreach ( $blocks as $block ) {
		$binding = $block['attrs']['metadata']['data-types/binding'] ?? null;

		if ( is_null( $binding ) || 'post_content' === $binding ) {
			continue;
		}

		$meta_fields[] = $binding;
	}

	return $meta_fields;
}

/**
 * Get all registered data types.
 */
function get_registered_data_types() {
	$data_types = get_posts( array( 'post_type' => 'data_types' ) );

	return array_map(
		function ( $data_type ) {
			return (object) array(
				'slug'     => $data_type->post_name,
				'name'     => $data_type->post_title,
				'template' => $data_type->post_content,
			);
		},
		$data_types
	);
}

/**
 * Get all register data type slugs.
 */
function get_data_type_slugs() {
	return array_map( fn( $data_type ) => $data_type->slug, get_registered_data_types() );
}

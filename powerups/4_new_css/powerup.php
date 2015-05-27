<?php

function powerup_new_css() {
	paf_options( array(
		'eoi_powerup_new_css' => array(
			'type' => 'checkbox',
			'options' => array(
				'on' => __( 'Enabled (recommended)' ),
			),
			'page' => 'eoi_powerups',
			'title' => __( 'New CSS' ),
			'description' => sprintf( '<p class="description eoi_powerup_description">%s</p>', __( 'Enhances the base CSS for responsiveness and compatibility.' ) ),
		)
	) );
}

function powerup_new_css_set_active( $active ) {
	$paf = get_option( 'paf' );
	$key = 'eoi_powerup_new_css';

	if ( $active ) {
		$paf[ $key ] = array( 'on' );
		powerup_new_css_on_activate();
	} else {
		$paf[ $key ] = array();
		powerup_new_css_on_deactivate();
	}

	update_option( 'paf', $paf );
}

function powerup_new_css_on_activate() {
	$new_css = new EoiNewCssMigration();
	$new_css->migrate();
}

function powerup_new_css_on_deactivate() {
	$new_css = new EoiNewCssMigration();
	$new_css->migrate();
}

class EoiNewCssMigration {
	private $layouts;
	private $layout_ids;

	public function migrate() {
		foreach ( get_posts( array( 'post_type' => 'easy-opt-ins', 'posts_per_page' => - 1 ) ) as $form ) {
			$this->migrate_form( $form );
		}
	}

	private function migrate_form( $form ) {
		$form_id = $form->ID;
		$fca_eoi = get_post_meta( $form_id, 'fca_eoi', true );

		$fca_eoi_changed = false;
		foreach ( $this->get_layout_ids() as $layout_id ) {
			if ( $layout_id == 'layout_2' || $layout_id == 'postbox_2' || $layout_id == 'lightbox_2' ) {
				$this->prepare_for_layout_2( $layout_id, $fca_eoi );
			}

			if ( $this->migrate_layout( $layout_id, $fca_eoi ) ) {
				$fca_eoi_changed = true;
			}
		}

		if ( $fca_eoi_changed ) {
			delete_post_meta( $form_id, 'fca_eoi' );
			add_post_meta( $form_id, 'fca_eoi', $fca_eoi );
		}
	}

	private function prepare_for_layout_2( $layout_id, &$fca_eoi ) {
		if ( empty( $fca_eoi[ $layout_id ] ) ) {
			return;
		}

		foreach ( $fca_eoi[ $layout_id ] as $main_selector => $attributes ) {
			foreach ( $attributes as $sub_selector => $value ) {
				if ( $sub_selector == 'border-top-color' ) {
					$fca_eoi[ $layout_id ][ $main_selector ]['fill'] = $value;
					unset( $fca_eoi[ $layout_id ][ $main_selector ][ $sub_selector ] );
				} elseif ( $sub_selector == 'fill' ) {
					$fca_eoi[ $layout_id ][ $main_selector ]['border-top-color'] = $value;
					unset( $fca_eoi[ $layout_id ][ $main_selector ][ $sub_selector ] );
				}
			}
		}
	}

	private function migrate_layout( $layout_id, &$fca_eoi ) {
		$layouts = $this->get_layouts();
		$fca_eoi_changed = false;

		if ( ! empty( $fca_eoi[ $layout_id ] ) ) {
			$old_selectors = $layouts[ $layout_id ]['old'];
			$new_selectors = $layouts[ $layout_id ]['new'];

			for ( $i = 0, $len = count( $old_selectors ); $i < $len; $i++ ) {
				$old_selector = $old_selectors[ $i ];
				$new_selector = $new_selectors[ $i ];

				if ( ! empty( $fca_eoi[ $layout_id ][ $old_selector ] ) ) {
					$fca_eoi_changed = true;
					$this->migrate_selector( $old_selector, $new_selector, $layout_id, $fca_eoi );
				} elseif ( ! empty( $fca_eoi[ $layout_id ][ $new_selector ] ) ) {
					$fca_eoi_changed = true;
					$this->migrate_selector( $new_selector, $old_selector, $layout_id, $fca_eoi );
				}
			}
		}

		return $fca_eoi_changed;
	}

	private function migrate_selector( $from_selector, $to_selector, $layout_id, &$fca_eoi ) {
		$fca_eoi[ $layout_id ][ $to_selector ] = $fca_eoi[ $layout_id ][ $from_selector ];
		unset( $fca_eoi[ $layout_id ][ $from_selector ] );
	}

	private function get_layouts() {
		if ( ! empty( $this->layouts ) ) {
			return $this->layouts;
		}

		$layouts = array();

		foreach ( glob( FCA_EOI_PLUGIN_DIR . 'layouts/*/*/layout-new.php' ) as $path ) {
			if ( strpos( $path, '/common/' ) !== false ) {
				continue;
			}

			$path_info  = pathinfo( $path );
			$layout_id  = basename( $path_info['dirname'] );
			$old_layout = $path_info['dirname'] . DIRECTORY_SEPARATOR . 'layout.php';
			$new_layout = $path;

			$layouts[ $layout_id ] = array(
				'old' => null,
				'new' => null
			);

			$layout = null;

			require $old_layout;
			$layouts[ $layout_id ]['old'] = $this->extract_main_selectors_from_layout( $layout );

			require $new_layout;
			$layouts[ $layout_id ]['new'] = $this->extract_main_selectors_from_layout( $layout );
		}

		$this->layouts = $layouts;
		return $layouts;
	}

	private function extract_main_selectors_from_layout( $layout ) {
		if ( empty( $layout['editables'] ) ) {
			return array();
		}

		$selectors = array();

		foreach ( $layout['editables'] as $parts ) {
			$selectors = array_merge( $selectors, array_keys( $parts ) );
		}

		return $selectors;
	}

	private function get_layout_ids() {
		if ( empty( $this->layout_ids ) ) {
			$this->layout_ids = array_keys( $this->get_layouts() );
		}
		return $this->layout_ids;
	}
}
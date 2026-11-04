import { colord, extend } from 'colord';
import a11yPlugin from 'colord/plugins/a11y';

( ( $ ) => {
	extend( [ a11yPlugin ] );

	const Field = acf.Field.extend( {
		type: 'color_palette',
		wait: 'ready',
		events: {
			'change input[type="radio"]': 'onChange',
			'click .components-circular-option-picker__clear': 'onClear',
		},
		$indicator() {
			return this.$( '.component-color-indicator' );
		},
		$input() {
			return this.$( 'input[type="radio"]:checked' );
		},
		initialize() {
			this.setColor( this.$input() );
		},
		onChange( e, $input ) {
			this.setColor( $input );
		},
		onClear( e ) {
			e.preventDefault();
			const $input = this.$input();
			if ( ! $input.length ) {
				return;
			}

			$input.prop( 'checked', false );
			this.$indicator().removeAttr( 'style' );

			// Unchecking a radio fires no change event: ACF (unsaved changes, change_field, conditional logic)
			// listens to change events in the field, so fire one on the hidden input, which holds the empty value.
			this.$( 'input[type="hidden"]' ).trigger( 'change' );
		},
		setColor( $input ) {
			if ( ! $input.length ) {
				return;
			}

			const $wrapper = $input.parent();
			const color = $wrapper.attr( 'data-color' );
			const parsed = colord( color );

			// The shorthand also drops the "no color" stripe of the block editor indicator.
			this.$indicator().css( 'background', color );

			// Checkmark with the best contrast against the swatch.
			$wrapper
				.find( 'svg' )
				.attr(
					'fill',
					parsed.contrast() > parsed.contrast( '#000' )
						? '#fff'
						: '#000'
				);
		},
	} );
	acf.registerFieldType( Field );
} )( jQuery );

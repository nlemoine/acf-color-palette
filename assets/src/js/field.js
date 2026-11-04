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
			this.$input().prop( 'checked', false );
			this.$indicator().removeAttr( 'style' );
		},
		setColor( $input ) {
			if ( ! $input.length ) {
				return;
			}

			const $wrapper = $input.parent();
			const color = $wrapper.attr( 'data-color' );
			const parsed = colord( color );

			this.$indicator().css( 'background-color', color );

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

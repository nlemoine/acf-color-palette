import { colord, extend } from 'colord';
import a11yPlugin from 'colord/plugins/a11y';

( ( $, undefined ) => {
	extend( [ a11yPlugin ] );

	const Field = acf.Field.extend( {
		type: 'color_palette',
		wait: 'ready',
		events: {
			duplicateField: 'onDuplicate',
			'change input[type="radio"]': 'onChange',
			'click .components-circular-option-picker__clear': 'onClear',
		},
		$indicator: function () {
			return this.$( '.component-color-indicator' );
		},
		$input: function () {
			return this.$( 'input:checked' );
		},
		initialize: function () {
			this.setColor( this.$input() );
		},
		onChange: function ( e, $input ) {
			this.setColor( $input );
		},
		onDuplicate: function ( e, $el, $duplicate ) {
			// Fix duplicate until issue is resolved: https://github.com/AdvancedCustomFields/acf/issues/616
			$duplicate
				.find( '.components-circular-option-picker__option-wrapper' )
				.each( function ( i, $el ) {
					const $this = $( this );
					const $input = $this.find( 'input[type="radio"]' );
					const $label = $this.find( 'label' );
					$label.attr( 'for', $input.attr( 'id' ) );
				} );
		},
		onClear: function ( e, $button ) {
			e.preventDefault();
			// Clear checkboxes
			this.$input().prop( 'checked', false );
			// Remove indicator
			this.$indicator().removeAttr( 'style' );
		},
		setColor: function ( $input ) {
			const $colorWrapper = $input.parent();
			const color = $colorWrapper.attr( 'data-color' );
			const $icon = $colorWrapper.find( 'svg' );
			const colordColor = colord( color );

			// Set indicator color
			this.$indicator().css( 'background-color', color );

			// Set icon color depending on contrast
			$icon.attr(
				'fill',
				colordColor.contrast() > colordColor.contrast( '#000' )
					? '#fff'
					: '#000'
			);
		},
	} );
	acf.registerFieldType( Field );
} )( jQuery );

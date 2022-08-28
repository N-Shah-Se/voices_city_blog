( function( api ) {

	// Extends our custom "music-recording-studio" section.
	api.sectionConstructor['music-recording-studio'] = api.Section.extend( {

		// No events for this type of section.
		attachEvents: function () {},

		// Always make the section active.
		isContextuallyActive: function () {
			return true;
		}
	} );

} )( wp.customize );
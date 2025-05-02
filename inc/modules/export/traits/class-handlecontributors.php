<?php

namespace Pressbooks\Modules\Export\Traits;

use function Pressbooks\Utility\get_contributors_name_imploded;

/**
 * Trait HandleContributors
 * Common logic for formatting contributors for title pages in exports.
 */
trait HandleContributors {

	/**
	 * Get formatted contributor strings based on metadata.
	 *
	 * @param array $metadata Book metadata array.
	 * @return array Associative array of formatted contributor strings.
	 */
	protected function getFormattedContributors( array $metadata ): array {
		$contributors_data = [
			'authors' => null,
			'editors' => null,
			'translators' => null,
			'illustrators' => null,
			'contributors' => null,
		];

		if ( isset( $metadata['pb_authors'] ) && ! empty( $metadata['pb_authors'] ) ) {
			$contributors_data['authors'] = is_array( $metadata['pb_authors'] ) ? get_contributors_name_imploded( $metadata['pb_authors'] ) : $metadata['pb_authors'];
		}

		if ( isset( $metadata['pb_editors'] ) && ! empty( $metadata['pb_editors'] ) ) {
			$editors_raw = is_array( $metadata['pb_editors'] ) ? get_contributors_name_imploded( $metadata['pb_editors'] ) : $metadata['pb_editors'];
			$contributors_data['editors'] = __( 'Edited By ', 'pressbooks' ) . $editors_raw;
		}

		if ( isset( $metadata['pb_translators'] ) && ! empty( $metadata['pb_translators'] ) ) {
			$translators_raw = is_array( $metadata['pb_translators'] ) ? get_contributors_name_imploded( $metadata['pb_translators'] ) : $metadata['pb_translators'];
			$contributors_data['translators'] = __( 'Translated By ', 'pressbooks' ) . $translators_raw;
		}

		if ( isset( $metadata['pb_illustrators'] ) && ! empty( $metadata['pb_illustrators'] ) ) {
			$illustrators_raw = is_array( $metadata['pb_illustrators'] ) ? get_contributors_name_imploded( $metadata['pb_illustrators'] ) : $metadata['pb_illustrators'];
			$contributors_data['illustrators'] = __( 'Illustrated By ', 'pressbooks' ) . $illustrators_raw;
		}

		if ( isset( $metadata['pb_contributors'] ) && ! empty( $metadata['pb_contributors'] ) ) {
			$contributors_raw = is_array( $metadata['pb_contributors'] ) ? get_contributors_name_imploded( $metadata['pb_contributors'] ) : $metadata['pb_contributors'];
			$contributors_data['contributors'] = __( 'Contributors: ', 'pressbooks' ) . $contributors_raw;
		}

		return $contributors_data;
	}
}

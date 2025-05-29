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
		];

		if ( ! empty( $metadata['pb_authors'] ) ) {
			$contributors_data['authors'] = is_array( $metadata['pb_authors'] )
				? get_contributors_name_imploded( $metadata['pb_authors'] )
				: $metadata['pb_authors'];
		} elseif ( ! empty( $metadata['pb_editors'] ) ) {
			$raw = is_array( $metadata['pb_editors'] )
				? get_contributors_name_imploded( $metadata['pb_editors'] )
				: $metadata['pb_editors'];
			$contributors_data['editors'] = __( 'Edited by ', 'pressbooks' ) . $raw;
		}

		return $contributors_data;
	}
}

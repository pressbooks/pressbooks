<?php
/**
 * This class extends Puc_v4p1_Vcs_GitHubApi to use GitHub releases instead of zipballs.
 * See: https://github.com/YahnisElsts/plugin-update-checker/issues/93
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks;

/**
 * @see https://github.com/YahnisElsts/plugin-update-checker/issues/93
 * @see https://api.github.com/repos/pressbooks/pressbooks/releases
 */
class Updater extends \Puc_v4p2_Vcs_GitHubApi {

	/**
	 * @return null|\Puc_v4p2_Vcs_Reference
	 */
	public function getLatestRelease() {
		$reference = parent::getLatestRelease();
		if ( $reference && isset( $reference->apiResponse, $reference->apiResponse->assets, $reference->apiResponse->assets[0] ) ) {
			$reference->downloadUrl = $this->signDownloadUrl( $reference->apiResponse->assets[0]->browser_download_url );
		}
		return $reference;
	}
}

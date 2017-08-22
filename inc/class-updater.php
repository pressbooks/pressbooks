<?php
/**
 * This class extends Puc_v4p1_Vcs_GitHubApi to use GitHub releases instead of zipballs.
 * See: https://github.com/YahnisElsts/plugin-update-checker/issues/93
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks;

class Updater extends \Puc_v4p2_Vcs_GitHubApi {
	/**
			 * Get the latest release from GitHub.
			 *
			 * @return Puc_v4p1_Vcs_Reference|null
			 */
	public function getLatestRelease() {
		$reference = parent::getLatestRelease();
		if ($reference && isset($reference->apiResponse, $reference->apiResponse->assets, $reference->apiResponse->assets[0])) {
		    $reference->downloadUrl = $reference->apiResponse->assets[0]->browser_download_url;
		}
		return $reference;
	}
}

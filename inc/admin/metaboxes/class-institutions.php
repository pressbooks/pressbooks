<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Metaboxes;

use Pressbooks\Admin\Fields\Select;
use function Pressbooks\Metadata\get_institutions;

class Institutions extends Metabox
{
    public function __construct()
    {
        parent::__construct();

        $this->slug = 'institutions';
        $this->title = __('Institutions', 'pressbooks');
    }

    public function getFields(): array
    {
        return [
			new Select(
				name: 'pb_institutions',
				label: __( 'Institutions', 'pressbooks' ),
				description: __('This optional field can be used to display the institution(s) which created this resource. If your college or university is not listed, please contact your network manager.', 'pressbooks'),
				options: $this->getInstitutions(),
				multiple: true
			)
		];
    }

	public function getInstitutions(): array
	{
		$options = [];

		foreach ( get_institutions() as $region => $institutions ) {
			if (is_array($institutions)) {
				foreach( $institutions as $code => $institution ) {
					$options[$region][$code] = $institution['name'];
				}
			}
		}

		return $options;
	}
}

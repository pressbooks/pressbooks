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
    public function __construct(bool $expanded = false)
    {
        parent::__construct($expanded);

        $this->slug = 'institutions';
        $this->title = __('Institutions', 'pressbooks');
    }

    public function getFields(): array
    {
        return [
			new Select(
				name: 'pb_institutions',
				label: __( 'Institutions', 'pressbooks' ),
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

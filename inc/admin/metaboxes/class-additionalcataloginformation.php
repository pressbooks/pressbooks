<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Metaboxes;

use Pressbooks\Admin\Fields\Date as DateField;
use Pressbooks\Admin\Fields\Select;
use Pressbooks\Admin\Fields\TaxonomySelect;
use Pressbooks\Admin\Fields\Text;

class AdditionalCatalogInformation extends Metabox
{
    public function __construct(bool $expanded = false)
    {
        parent::__construct($expanded);

        $this->slug = 'additional-catalog-information';
        $this->title = __('Additional Catalog Information', 'pressbooks');
    }

    public function getFields(): array
    {
        return [];
    }
}

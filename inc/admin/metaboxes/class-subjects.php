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

class Subjects extends Metabox
{
    public function __construct(bool $expanded = false)
    {
        parent::__construct($expanded);

        $this->slug = 'subjects';
        $this->title = __('Subject(s)', 'pressbooks');
    }

    public function getFields(): array
    {
        return [];
    }
}

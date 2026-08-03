<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs\Storage;

enum TokenMode: string {
	case Direct = 'direct';
	case Broker = 'broker';
}

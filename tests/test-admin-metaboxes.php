<?php

use Pressbooks\Admin\Metaboxes\About;
use Pressbooks\Admin\Metaboxes\AdditionalCatalogInformation;

/**
 * @group metaboxes
 */
class Admin_Metaboxes extends \WP_UnitTestCase {
	use utilsTrait;

	public function set_up()
	{
		parent::set_up();
		$this->metadata = new \Pressbooks\Metadata();
	}

	public function test_render_metabox()
	{
		$book = \Pressbooks\Book::getInstance();

		$this->_book();

		$structure = $book::getBookStructure();

		global $post;

		$post = $this->metadata->getMetaPost();

		foreach([
			'Pressbooks\\Admin\\Metaboxes\\About',
			'Pressbooks\\Admin\\Metaboxes\\AdditionalCatalogInformation',
			'Pressbooks\\Admin\\Metaboxes\\Copyright',
			'Pressbooks\\Admin\\Metaboxes\\GeneralInformation',
			'Pressbooks\\Admin\\Metaboxes\\Institutions',
			'Pressbooks\\Admin\\Metaboxes\\Subjects'
		] as $classname) {
			$metabox = new $classname( true );
			ob_start();
			$metabox->render();
			$output = ob_get_clean();

			$this->assertStringContainsString($metabox->slug . '_nonce', $output);

			foreach($metabox->fields as $field) {
				$this->assertStringContainsString($field->name, $output);
			}
		}

		$post = get_post($structure['part'][0]['ID']);

		$metabox = new Pressbooks\Admin\Metaboxes\PartVisibility();
		ob_start();
		$metabox->render();
		$output = ob_get_clean();

		$this->assertStringContainsString($metabox->slug . '_nonce', $output);

		foreach($metabox->fields as $field) {
			$this->assertStringContainsString($field->name, $output);
		}

		$post = get_post($structure['part'][0]['chapters'][0]['ID']);

		$metabox = new Pressbooks\Admin\Metaboxes\SectionMetadata();
		ob_start();
		$metabox->render();
		$output = ob_get_clean();

		$this->assertStringContainsString($metabox->slug . '_nonce', $output);

		foreach($metabox->fields as $field) {
			$this->assertStringContainsString($field->name, $output);
		}
	}
}

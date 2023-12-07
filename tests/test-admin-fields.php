<?php

use Pressbooks\Admin\Fields\Date;
use Pressbooks\Admin\Fields\Text;
use Pressbooks\Admin\Fields\TextArea;
use Pressbooks\Admin\Fields\Url;

/**
 * @group fields
 */
class Admin_Fields extends \WP_UnitTestCase {
	use utilsTrait;

	public function set_up()
	{
		parent::set_up();

		$new_post['post_type'] = 'chapter';

		$GLOBALS['post'] = get_post( $this->factory()->post->create_object( [
			'post_type' => 'chapter'
		] ) );
	}

	public function test_sanitize_date() {
		$field = new Date( 'test', 'Test' );

		$this->assertEquals( 1639699200, $field->sanitize( '2021-12-17' ) );
	}

	public function test_sanitize_text() {
		$field = new Text( 'test', 'Test' );

		$this->assertEquals( 'Title', $field->sanitize( "<h2>Title</h2>" ) );
	}

	public function test_sanitize_text_with_html() {
		$field = new TextArea( 'test', 'Test' );

		$this->assertEquals( "<h2>Title</h2>", $field->sanitize( "<h2>Title</h2>" ) );
	}

	public function test_sanitize_url() {
		$field = new Url( 'test', 'Test' );

		$this->assertEquals( 'http://pressbooks.com', $field->sanitize( 'pressbooks.com' ) );
	}

	public function provideTextData(): array
	{
		return [
			'minimal parameters' => [
				'field' => '\\Pressbooks\\Admin\\Fields\\Text',
				'parameters' => [
					'name' => 'test',
					'label' => 'Test',
					'description' => null,
					'id' => null,
					'multiple' => false,
					'disabled' => false,
					'readonly' => false,
				],
				'expected_substrings' => [
					'type="text"',
					'name="test"',
					'id="test"',
					'for="test"'
				],
				'unexpected_substrings' => [
					'disabled',
					'readonly'
				]
			],
			'custom id' => [
				'field' => '\\Pressbooks\\Admin\\Fields\\Text',
				'parameters' => [
					'name' => 'test',
					'label' => 'Test',
					'description' => null,
					'id' => 'test_field',
					'multiple' => false,
					'disabled' => false,
					'readonly' => false,
				],
				'expected_substrings' => [
					'id="test_field"',
					'for="test_field"'
				],
				'unexpected_substrings' => []
			],
			'description' => [
				'field' => '\\Pressbooks\\Admin\\Fields\\Text',
				'parameters' => [
					'name' => 'test',
					'label' => 'Test',
					'description' => 'A test field.',
					'id' => null,
					'multiple' => false,
					'disabled' => false,
					'readonly' => false,
				],
				'expected_substrings' => [
					'aria-describedby="test-description"',
					'<p class="description" id="test-description">',
					'A test field.'
				],
				'unexpected_substrings' => []
			],
			'disabled' => [
				'field' => '\\Pressbooks\\Admin\\Fields\\Text',
				'parameters' => [
					'name' => 'test',
					'label' => 'Test',
					'description' => null,
					'id' => null,
					'multiple' => false,
					'disabled' => true,
					'readonly' => false,
				],
				'expected_substrings' => [
					' disabled '
				],
				'unexpected_substrings' => []
			],
			'readonly' => [
				'field' => '\\Pressbooks\\Admin\\Fields\\Text',
				'parameters' => [
					'name' => 'test',
					'label' => 'Test',
					'description' => null,
					'id' => null,
					'multiple' => false,
					'disabled' => false,
					'readonly' => true,
				],
				'expected_substrings' => [
					' readonly '
				],
				'unexpected_substrings' => []
			],
			'minimal multiple' => [
				'field' => '\\Pressbooks\\Admin\\Fields\\Text',
				'parameters' => [
					'name' => 'test',
					'label' => 'Test',
					'description' => null,
					'id' => null,
					'multiple' => true,
					'disabled' => false,
					'readonly' => false,
				],
				'expected_substrings' => [
					'type="text"',
					'name="test[]"',
					'id="test-1"',
					'aria-labelledby="test-label"'
				],
				'unexpected_substrings' => [
					'disabled',
					'readonly'
				]
			],
		];
	}

	/**
	 * @dataProvider provideTextData
	 */
	public function test_render_text( string $field, array $parameters, array $expected_substrings, array $unexpected_substrings ): void
	{
		$rendered_field = (new $field(
			name: $parameters['name'],
			label: $parameters['label'],
			description: $parameters['description'],
			id: $parameters['id'],
			multiple: $parameters['multiple'],
			disabled: $parameters['disabled'],
			readonly: $parameters['readonly']
		))->render();

		foreach ($expected_substrings as $substring) {
			$this->assertStringContainsString( $substring, $rendered_field );
		}

		foreach ($unexpected_substrings as $substring) {
			$this->assertStringNotContainsString( $substring, $rendered_field );
		}
	}
}

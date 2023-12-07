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

	public function provideInputData(): array
	{
		$defaults = [
			'name' => 'test',
			'label' => 'Test',
			'description' => null,
			'id' => null,
			'multiple' => false,
			'disabled' => false,
			'readonly' => false,
		];

		return [
			'minimal parameters' => [
				'parameters' => $defaults,
				'expected_substrings' => [
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
				'parameters' => array_merge($defaults, [
					'id' => 'test_field',
				]),
				'expected_substrings' => [
					'id="test_field"',
					'for="test_field"'
				],
				'unexpected_substrings' => []
			],
			'description' => [
				'parameters' => array_merge($defaults, [
					'description' => 'A test field.'
				]),
				'expected_substrings' => [
					'aria-describedby="test-description"',
					'<p class="description" id="test-description">',
					'A test field.'
				],
				'unexpected_substrings' => []
			],
			'disabled' => [
				'parameters' => array_merge($defaults, [
					'disabled' => true
				]),
				'expected_substrings' => [
					' disabled '
				],
				'unexpected_substrings' => []
			],
			'readonly' => [
				'parameters' => array_merge($defaults, [
					'readonly' => true
				]),
				'expected_substrings' => [
					' readonly '
				],
				'unexpected_substrings' => []
			],
			'minimal multiple' => [
				'parameters' => array_merge($defaults, [
					'multiple' => true
				]),
				'expected_substrings' => [
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
	 * @dataProvider provideInputData
	 */
	public function test_render_text( array $parameters, array $expected_substrings, array $unexpected_substrings ): void
	{
		$rendered_field = (new Text(
			name: $parameters['name'],
			label: $parameters['label'],
			description: $parameters['description'],
			id: $parameters['id'],
			multiple: $parameters['multiple'],
			disabled: $parameters['disabled'],
			readonly: $parameters['readonly']
		))->render();

		$this->assertStringContainsString( 'type="text"', $rendered_field );

		foreach ($expected_substrings as $substring) {
			$this->assertStringContainsString( $substring, $rendered_field );
		}

		foreach ($unexpected_substrings as $substring) {
			$this->assertStringNotContainsString( $substring, $rendered_field );
		}
	}

	/**
	 * @dataProvider provideInputData
	 */
	public function test_render_url( array $parameters, array $expected_substrings, array $unexpected_substrings ): void
	{
		$rendered_field = (new Url(
			name: $parameters['name'],
			label: $parameters['label'],
			description: $parameters['description'],
			id: $parameters['id'],
			multiple: $parameters['multiple'],
			disabled: $parameters['disabled'],
			readonly: $parameters['readonly']
		))->render();

		$this->assertStringContainsString( 'type="url"', $rendered_field );

		foreach ($expected_substrings as $substring) {
			$this->assertStringContainsString( $substring, $rendered_field );
		}

		foreach ($unexpected_substrings as $substring) {
			$this->assertStringNotContainsString( $substring, $rendered_field );
		}
	}
}

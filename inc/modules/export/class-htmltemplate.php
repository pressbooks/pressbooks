<?php

namespace Pressbooks\Modules\Export;

trait HtmlTemplate {
	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	public function createFrontMatter( $book_contents, $metadata ) {
		$front_matter_printf = '<div class="front-matter %1$s" id="%2$s">';
		$front_matter_printf .= '<div class="front-matter-title-wrap"><h3 class="front-matter-number">%3$s</h3><h1 class="front-matter-title">%4$s</h1></div>';
		$front_matter_printf .= '<div class="ugc front-matter-ugc">%5$s</div>%6$s';
		$front_matter_printf .= '</div>';

		$vars = [
			'post_title' => '',
			'stylesheet' => $this->stylesheet,
			'post_content' => '',
			'isbn' => ( isset( $metadata['pb_ebook_isbn'] ) ) ? $metadata['pb_ebook_isbn'] : '',
			'lang' => $this->lang,
		];

		$i = $this->frontMatterPos;
		foreach ( [ 'before-title' ] as $compare ) {
			foreach ( $book_contents['front-matter'] as $front_matter ) {

				if ( ! $front_matter['export'] ) {
					continue; // Skip
				}

				$front_matter_id = $front_matter['ID'];
				$subclass = $this->taxonomy->getFrontMatterType( $front_matter_id );

				if ( $compare !== $subclass ) {
					continue; //Skip
				}

				$slug = $front_matter['post_name'];
				$title = ( get_post_meta( $front_matter_id, 'pb_show_title', true ) ? $front_matter['post_title'] : '' );
				$content = $this->kneadHtml( $front_matter['post_content'], 'front-matter', $i );

				$vars['post_title'] = $front_matter['post_title'];
				$vars['post_content'] = sprintf(
					$front_matter_printf,
					$subclass,
					$slug,
					$i,
					Sanitize\decode( $title ),
					$content,
					''
				);

				$file_id = 'front-matter-' . sprintf( '%03s', $i );
				$filename = "{$file_id}-{$slug}.{$this->filext}";

				\Pressbooks\Utility\put_contents(
					$this->tmpDir . "/EPUB/$filename",
					$this->loadTemplate( $this->dir . '/templates/epub201/html.php', $vars )
				);

				$this->manifest[ $file_id ] = [
					'ID' => $front_matter['ID'],
					'post_title' => $front_matter['post_title'],
					'filename' => $filename,
				];

				++$i;
			}
		}
		$this->frontMatterPos = $i;
	}
}

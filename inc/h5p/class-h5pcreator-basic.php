<?php

namespace Pressbooks\H5P;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use H5P_Plugin;

class H5PCreatorBasic {

	public static H5PCreator|null $instance = null;

	static public function init(): H5PCreator
	{
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks(self::$instance);
		}
		return self::$instance;
	}

	static public function hooks( H5PCreator $obj ): void
	{
		add_action('admin_menu', function() use ($obj) {
			add_menu_page('Create H5P Activity', 'Create H5P Activity', 'manage_options', 'create-h5p-activity', [
				$obj,
				'createH5p'
			]);
		});
	}

	/**
	 * @throws GuzzleException
	 */
	public function createH5p(): void
	{
		switch_to_blog(23);
		$chapter_id = 28;
		$post = get_post($chapter_id);
		$plain_text_content = wp_strip_all_tags($post->post_content);

		$questions = $this->generateFillInTheBlanks($plain_text_content);

		$plugin = H5P_Plugin::get_instance();
		$interface = $plugin->get_h5p_instance('interface');
		$h5p_core = $plugin->get_h5p_instance('core');

		$content = [
			'library' => [
				'libraryId' => 18,
				'machineName' => 'H5P.Blanks',
				'majorVersion' => 1,
				'minorVersion' => 14,
			],
			'metadata' => [
				'title' => 'H5P test',
				'description' => 'This is a question set created programmatically',
			],
			'disable' => false,
			'params' => json_encode([
				'media' => [
					'type' => ['params' => []],
					'disableImageZooming' => false,
				],
				'text' => '<p>' . $post->post_title . '</p>',
				'overallFeedback' => [
					'from' => 0,
					'to' => 100,
				],
				'showSolutions' => 'Show solution',
				'tryAgain' => 'Retry',
				'checkAnswer' => 'Check',
				'submitAnswer' => 'Submit',
				'notFilledOut' => 'Please fill in all blanks to view solution',
				'answerIsCorrect' => "':ans' is correct",
				'answerIsWrong' => "':ans' is wrong",
				'answeredCorrectly' => 'Answered correctly',
				'answeredIncorrectly' => 'Answered incorrectly',
				'solutionLabel' => 'Correct answer:',
				'inputLabel' => 'Blank input @num of @total',
				'inputHasTipLabel' => 'Tip available',
				'tipLabel' => 'Tip',
				'behaviour' => [
					'enableRetry' => true,
					'enableSolutionsButton' => true,
					'enableCheckButton' => true,
					'autoCheck' => false,
					'caseSensitive' => true,
					'showSolutionsRequiresInput' => true,
					'separateLines' => false,
					'confirmCheckDialog' => false,
					'confirmRetryDialog' => false,
					'acceptSpellingErrors' => false,
				],
				'scoreBarLabel' => 'You got :num out of :total points',
				'a11yCheck' => 'Check the answers. The responses will be marked as correct, incorrect, or unanswered.',
				'a11yShowSolution' => 'Show the solution. The task will be marked with its correct solution.',
				'a11yRetry' => 'Retry the task. Reset all responses and start the task over again.',
				'a11yCheckingModeHeader' => 'Checking mode',
				'confirmCheck' => [
					'header' => 'Finish ?',
					'body' => 'Are you sure you wish to finish ?',
					'cancelLabel' => 'Cancel',
					'confirmLabel' => 'Finish',
				],
				'confirmRetry' => [
					'header' => 'Retry ?',
					'body' => 'Are you sure you wish to retry ?',
					'cancelLabel' => 'Cancel',
					'confirmLabel' => 'Confirm',
				],
				'questions' => $questions,
			], JSON_UNESCAPED_SLASHES),
		];

		$h5p_core->h5pF = $interface;

		$content['filtered'] = $h5p_core->filterParameters($content['params']);

		$content_id = $h5p_core->saveContent($content);

		$h5p_shortcode = '[h5p id="' . $content_id . '"]';


		$updated_content = $post->post_content . "\n\n" . $h5p_shortcode;
		wp_update_post(array(
			'ID' => $chapter_id,
			'post_content' => $updated_content
		));

		restore_current_blog();

		echo 'H5P Content created and inserted into chapter with ID: ' . $chapter_id;
	}

	/**
	 * @throws GuzzleException
	 */
	public function generateFillInTheBlanks(string $text): array {
		$url = 'https://api.openai.com/v1/chat/completions';

		// Your OpenAI API key (retrieve securely)
		$api_key = env('OPENAI_API_KEY');

		$prompt =  "Create three fill in the blank texts from the following content to be provided.
		The format of your output should be: \nQ: <TEXT 1 HERE>\nQ: <TEXT 2 HERE>\nQ: <TEXT 3 HERE>\n
		Here's an example of a text I am expecting: The capital of France is *Paris*. Make sure tu include the * character
		which indicates that will be a blank space to complete, and the correct answer is between *.
		I don't want you to use exact phrases from the text, I want my student to help them to understand the content.
		Also, use one or two words as a text to fill, and avoid punctuaction as a part of the response.\n
		Here's the text where you should create the fill in the blank texts: \n" . $text;

		$data = array(
			'model' => 'gpt-4o-mini',
			'messages' => array(
				array('role' => 'user', 'content' => $prompt)
			),
			'temperature' => 0.7
		);

		$guzzleClient = new Client();

		$response = $guzzleClient->request('POST', $url, [
			'headers' => [
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $api_key
			],
			'json' => $data,
		]);
		// if error
		if ($response->getStatusCode() !== 200) {
			dump($response->getBody()->getContents());
			throw new \Exception('Error generating fill-in-the-blanks questions');
		}

		$response = json_decode($response->getBody()->getContents(), true);

		$generatedText = $response['choices'][0]['message']['content'];

		/**
		 * Example of generated text:
		 * Q1: Critical thinking is a broad term used in nursing that includes “reasoning about clinical issues such as *teamwork*, collaboration, and streamlining workflow.”
		 * Q2: Clinical judgment is defined by the National Council of State Boards of Nursing (NCSBN) as, “The observed outcome of critical thinking and *decision-making*.”
		 * Q3: Evidence-based practice (EBP) is defined by the American Nurses Association (ANA) as, “A lifelong problem-solving approach that integrates the best evidence from well-designed research studies and *evidence-based theories*.”
		 */
		// separate questions to return 3 of them in an array
		$questions = explode("\n", $generatedText);
		// remove Q1: Q2: Q3: from each question
		return array_map(function($question) {
			return trim(str_replace('Q: ', '', $question));
		}, $questions);
	}
}

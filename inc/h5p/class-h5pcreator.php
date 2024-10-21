<?php

namespace Pressbooks\h5p;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use H5P_Plugin;
use WP_Post;

class H5PCreator {
	private array $supportedH5pTypes = [
		'fill in the blanks' => 'H5P.Blanks',
		'true or false' => 'H5P.TrueFalse',
	];

	private string $apiUrl;

	private string $apiKey;

	public function __construct() {
		$this->apiUrl = 'https://api.openai.com/v1/chat/completions';

		$this->apiKey = env('OPENAI_API_KEY', '');
	}

	/**
	 * @throws GuzzleException
	 */
	public function create(int $postId, WP_Post $post, bool $update): void
	{
		if (wp_is_post_autosave($postId) || !$update) {
			return;
		}

		if (empty($this->apiKey)) {
			return;
		}

		$blogId = get_current_blog_id();
		if ($blogId === get_main_site_id()) {
			return;
		}

		$postContent = $post->post_content;

		preg_match_all('/\[h5p_ai type=["\']?([^\]"\'\]]+)["\']?\]/', $postContent, $matches, PREG_OFFSET_CAPTURE);

		if (empty($matches[0])) {
			return;
		}

		switch_to_blog($blogId);

		$replacements = [];
		$previousPosition = 0;

		foreach ($matches[0] as $index => $match) {
			$fullMatch = $match[0]; // The matched shortcode, e.g., '[h5p_ai type="fill in the blanks"]'
			$position = $match[1];  // Position of the match in $postContent
			$type = $matches[1][$index][0]; // The type, e.g., 'fill in the blanks'

			if (array_key_exists($type, $this->supportedH5pTypes)) {
				// Get the text between previousPosition and current shortcode position
				$textContent = substr($postContent, $previousPosition, $position - $previousPosition);

				// Create the H5P content
				$h5pId = $this->buildH5p($type, $textContent);

				if ($h5pId === 0) {
					// Handle error (you can log or display an error message)
					continue;
				}

				// Prepare replacement
				$replacements[] = [
					'position' => $position,
					'length' => strlen($fullMatch),
					'replacement' => '[h5p id="' . $h5pId . '"]',
				];

				// Update previousPosition to after the current shortcode
				$previousPosition = $position + strlen($fullMatch);
			}
		}

		// Perform replacements from the end to the beginning to avoid shifting positions
		usort($replacements, function($a, $b) {
			return $b['position'] - $a['position'];
		});

		foreach ($replacements as $replacement) {
			$postContent = substr_replace(
				$postContent,
				$replacement['replacement'],
				$replacement['position'],
				$replacement['length']
			);
		}

		// Save the updated post content
		wp_update_post([
			'ID' => $postId,
			'post_content' => $postContent,
		]);

		restore_current_blog();
	}

	private function sanitizeContent(string $textContent): string
	{
		return wp_strip_all_tags($textContent);
	}

	/**
	 * @throws GuzzleException
	 */
	private function buildH5p(string $type, string $textContent): int
	{
		$textContent = $this->sanitizeContent($textContent);

		return match ($type) {
			'fill in the blanks' => $this->createFillInTheBlanks($textContent),
			'true or false' => $this->createTrueOrFalse($textContent),
			default => 0,
		};
	}

	/**
	 * @throws GuzzleException
	 */
	private function createTrueOrFalse(string $textContent): int
	{
		$plugin = H5P_Plugin::get_instance();
		$interface = $plugin->get_h5p_instance('interface');
		$h5p_core = $plugin->get_h5p_instance('core');
		$h5p_core->h5pF = $interface;

		$generated = $this->generateTrueOrFalse($textContent);

		$content = [
			'library' => [
				'libraryId' => 7,
				'machineName' => 'H5P.TrueFalse',
				'majorVersion' => 1,
				'minorVersion' => 8,
			],
			'metadata' => [
				'title' => $generated['title'],
				'description' => 'This is a question set created programmatically',
			],
			'disable' => false,
			'params' => json_encode([
				'media' => [
					'type' => [
						'params' => [
							'decorative' => false,
							'contentName' => 'Image',
							'expandImage' => 'Expand Image',
							'minimizeImage' => 'Minimize Image',
						],
						'library' => 'H5P.Image 1.1',
						'metadata' => [
							'contentType' => 'Image',
							'license' => 'U',
							'title' => 'Image',
						],
						'disableImageZooming' => false,
					],
				],
				'correct' => $generated['answer'],
				'behaviour' => [
					'enableRetry' => true,
					'enableSolutionsButton' => true,
					'enableCheckButton' => true,
					'confirmCheckDialog' => false,
					'confirmRetryDialog' => false,
					'autoCheck' => false,
				],
				'l10n' => [
					'trueText' => 'True',
					'falseText' => 'False',
					'score' => 'You got @score of @total points',
					'checkAnswer' => 'Check',
					'submitAnswer' => 'Submit',
					'showSolutionButton' => 'Show solution',
					'tryAgain' => 'Retry',
					'wrongAnswerMessage' => 'Wrong answer',
					'correctAnswerMessage' => 'Correct answer',
					'scoreBarLabel' => 'You got :num out of :total points',
					'a11yCheck' => 'Check the answers. The responses will be marked as correct, incorrect, or unanswered.',
					'a11yShowSolution' => 'Show the solution. The task will be marked with its correct solution.',
					'a11yRetry' => 'Retry the task. Reset all responses and start the task over again.',
				],
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
				'question' => $generated['question'],
			], JSON_UNESCAPED_SLASHES),
		];

		$content['filtered'] = $h5p_core->filterParameters($content['params']);

		return $h5p_core->saveContent($content);
	}

	/**
	 * @throws GuzzleException
	 */
	private function createFillInTheBlanks(string $textContent): int
	{
		$generated = $this->generateFillInTheBlanks($textContent);

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
				'title' => $generated['title'],
				'description' => 'This is a question set created programmatically',
			],
			'disable' => false,
			'params' => json_encode([
				'media' => [
					'type' => ['params' => []],
					'disableImageZooming' => false,
				],
				'text' => '<p>' . $generated['title'] . '</p>',
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
				'questions' => $generated['questions'],
			], JSON_UNESCAPED_SLASHES),
		];

		$h5p_core->h5pF = $interface;

		$content['filtered'] = $h5p_core->filterParameters($content['params']);

		return $h5p_core->saveContent($content);
	}

	/**
	 * @throws GuzzleException
	 * @throws \Exception
	 */
	public function generateTrueOrFalse(string $text): array {
		$prompt = "As an instructor I want you to create a true or false question from the following content to be provided.
		The format of your output should be: \nT: <TITLE OF CHALLENGE HERE> \nQ: <QUESTION TEXT HERE>\n R: <TRUE OR FALSE>\n
		Here's an example of one expected output: \n
		T: Learning capitals of countries Q: The capital of France is Buenos Aires R: false\n\n
		Here's the text where you should create the true or false question: \n" . $text;

		$data = array(
			'model' => 'gpt-4o-mini',
			'messages' => array(
				array('role' => 'user', 'content' => $prompt)
			),
			'temperature' => 0.7
		);

		$guzzleClient = new Client();

		$response = $guzzleClient->request('POST', $this->apiUrl, [
			'headers' => [
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $this->apiKey,
			],
			'json' => $data,
		]);

		if ($response->getStatusCode() !== 200) {
			throw new \Exception('Error generating true or false question');
		}

		$response = json_decode($response->getBody()->getContents(), true);

		$generatedText = $response['choices'][0]['message']['content'];

		$title = trim(str_replace('T: ', '', explode("\n", $generatedText)[0]));
		$question = trim(str_replace('Q: ', '', explode("\n", $generatedText)[1]));
		$answer = trim(str_replace('R: ', '', explode("\n", $generatedText)[2]));

		return [
			'title' => $title,
			'question' => $question,
			'answer' => strtolower($answer),
		];
	}

	/**
	 * @throws GuzzleException
	 * @throws \Exception
	 */
	public function generateFillInTheBlanks(string $text): array {
		$prompt =  "As an instructor I want you to create three fill in the blank texts from the following content to be provided.
		The format of your output should be: \nT: <TITLE OF CHALLENGE HERE> Q: <TEXT 1 HERE>\nQ: <TEXT 2 HERE>\nQ: <TEXT 3 HERE>\n
		Here's an example of one of the questions text I am expecting: The capital of France is *Paris*. Make sure tu include the * character
		which indicates that will be a blank space to complete, and the correct answer is between *.\n
		For the <TITLE OF CHALLENGE HERE> placeholder I hope you can provide a title that describes the text to be completed.\n
		Don't use exact words or phrases from the text to create the answers, use synonims where convenient. Create the output in a way that
		by analyzing their responses, it would allow me to confirm that my students really understand the topic,
		I am not just only interested to know if they read it, but mainly if they comprehend it.
		Also, use only one or two words as a text to fill, and avoid punctuaction as a part of the response.\n
		Here's the text from where you should create the fill in the blank texts: \n" . $text;

		$data = array(
			'model' => 'gpt-4o-mini',
			'messages' => array(
				array('role' => 'user', 'content' => $prompt)
			),
			'temperature' => 0.7
		);

		$guzzleClient = new Client();

		$response = $guzzleClient->request('POST', $this->apiUrl, [
			'headers' => [
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $this->apiKey,
			],
			'json' => $data,
		]);

		if ($response->getStatusCode() !== 200) {
			throw new \Exception('Error generating fill-in-the-blanks questions');
		}

		$response = json_decode($response->getBody()->getContents(), true);

		$generatedText = $response['choices'][0]['message']['content'];

		// extract the title from the generated text
		$title = trim(str_replace('T: ', '', explode("\n", $generatedText)[0]));
		// remove the title from the generated text
		$generatedText = substr($generatedText, strpos($generatedText, "\n") + 1);

		return [
			'title' => $title,
			'questions' => array_map(function($question) {
				return trim(str_replace('Q: ', '', $question));
			}, explode("\n", $generatedText)),
		];
	}
}

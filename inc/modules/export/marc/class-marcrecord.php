<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Export\Marc;

/**
 * Represents a MARC21 bibliographic record
 */
class MarcRecord {

	/**
	 * MARC record leader (24 characters)
	 *
	 * @var string
	 */
	protected string $leader = '';

	/**
	 * Control fields (001-009)
	 *
	 * @var array
	 */
	protected array $controlFields = [];

	/**
	 * Data fields (010-999)
	 *
	 * @var array
	 */
	protected array $dataFields = [];

	/**
	 * Constructor
	 */
	public function __construct() {
		// Set default leader for books
		// Position 00-04: Record length (calculated later)
		// Position 05: Record status (n = new)
		// Position 06: Type of record (a = language material)
		// Position 07: Bibliographic level (m = monograph/item)
		// Position 08: Type of control (space = no specified type)
		// Position 09: Character coding scheme (a = UCS/Unicode)
		// Position 10-11: Indicator count, subfield code count (2, 2)
		// Position 12-16: Base address of data (calculated later)
		// Position 17: Encoding level (space = full level)
		// Position 18: Descriptive cataloging form (i = ISBD)
		// Position 19: Multipart resource record level (space = not specified)
		// Position 20-23: Entry map (4500 = default)
		$this->leader = '00000nam a2200000 i 4500';
	}

	/**
	 * Set leader
	 *
	 * @param string $leader
	 */
	public function setLeader( string $leader ): void {
		$this->leader = $leader;
	}

	/**
	 * Get leader
	 *
	 * @return string
	 */
	public function getLeader(): string {
		return $this->leader;
	}

	/**
	 * Add control field
	 *
	 * @param string $tag Field tag (001-009)
	 * @param string $data Field data
	 */
	public function addControlField( string $tag, string $data ): void {
		$this->controlFields[] = [
			'tag' => $tag,
			'data' => $data,
		];
	}

	/**
	 * Get control fields
	 *
	 * @return array
	 */
	public function getControlFields(): array {
		return $this->controlFields;
	}

	/**
	 * Add data field
	 *
	 * @param string $tag Field tag (010-999)
	 * @param string $ind1 First indicator
	 * @param string $ind2 Second indicator
	 * @param array  $subfields Array of subfields, each with 'code' and 'data' keys
	 */
	public function addDataField( string $tag, string $ind1, string $ind2, array $subfields ): void {
		$this->dataFields[] = [
			'tag' => $tag,
			'ind1' => $ind1,
			'ind2' => $ind2,
			'subfields' => $subfields,
		];
	}

	/**
	 * Get data fields
	 *
	 * @return array
	 */
	public function getDataFields(): array {
		return $this->dataFields;
	}

	/**
	 * Sort fields by tag
	 */
	public function sortFields(): void {
		// Sort control fields
		usort(
			$this->controlFields, function( $a, $b ) {
				return strcmp( $a['tag'], $b['tag'] );
			}
		);

		// Sort data fields
		usort(
			$this->dataFields, function( $a, $b ) {
				return strcmp( $a['tag'], $b['tag'] );
			}
		);
	}
}

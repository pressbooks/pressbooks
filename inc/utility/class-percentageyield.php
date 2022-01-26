<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Utility;

class PercentageYield {

	/**
	 * Starting percentage (between 1-100)
	 *
	 * @var int
	 */
	protected $start;

	/**
	 * Ending percentage (between 1-100)
	 *
	 * @var int
	 */
	protected $end;

	/**
	 * Total messages to yield (any number)
	 *
	 * @var int
	 */
	protected $total;

	/**
	 * Estimated chunks (modulo === 0)
	 *
	 * @var int
	 */
	protected $chunks;

	/**
	 * Current message
	 *
	 * @var int
	 */
	protected $i;

	/**
	 * Current percentage (%)
	 *
	 * @var int
	 */
	protected $j;

	/**
	 * PercentageYield constructor.
	 *
	 * @param int $start Starting percentage
	 * @param int $end Ending percentage
	 * @param int $total Total messages to yield
	 */
	public function __construct( $start, $end, $total ) {
		$this->start = max( 1, min( 100, $start ) );
		$this->end = max( 1, min( 100, $end ) );
		$this->total = $total;
		$range = $this->end - $this->start;
		if ( $range <= 0 ) {
			$this->chunks = 1;
		} else {
			$this->chunks = (int) max( round( $this->total / $range ), 1 );
		}
		$this->i = 1;
		$this->j = $this->start;
	}

	/**
	 * Automatically append something like (3 of 20) to $msg when yielding [$percentage => $msg]
	 * Automatically do calculations
	 *
	 * @param string $msg
	 * @param bool $emit (optional, default is true)
	 *
	 * @return \Generator
	 */
	public function tick( $msg, $emit = true ) : \Generator {
		$percentage = $this->j;
		if ( $percentage < $this->start ) {
			$percentage = $this->start;
		} elseif ( $percentage > $this->end ) {
			$percentage = $this->end;
		}
		$msg = trim( $msg ) . sprintf( __( ' (%1$d of %2$d)', 'pressbooks' ), $this->i, $this->total );
		if ( $emit ) {
			yield $percentage => $msg;
		}
		++$this->i;
		if ( $this->i % $this->chunks === 0 ) {
			++$this->j;
		}
	}

}

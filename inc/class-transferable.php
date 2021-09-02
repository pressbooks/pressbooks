<?php

namespace Pressbooks;

interface Transferable {
	/**
	 * Adds the required filters to handle exporting and importing data.
	 *
	 * @param $obj
	 * @return void
	 */
	public function bootExportable( Transferable $obj );

	/**
	 * Adds the download action to the bulk actions' dropdown.
	 *
	 * @param $actions
	 * @return array
	 */
	public function addBulkAction( $actions );

	/**
	 * Handles the action received.
	 *
	 * @param bool $redirect
	 * @param string $action
	 * @param array $ids
	 * @return void
	 */
	public function handleBulkAction( $redirect, $action, $ids );

	/**
	 * Renders the import form to allow importing taxonomies.
	 *
	 * @return void
	 */
	public function renderImportForm();

	/**
	 * Get the list of fields that should be trasfered.
	 *
	 * @return array
	 */
	public function getTransferableFields();

	/**
	 * Returns the form title and the hint for the file input.
	 *
	 * @return array
	 */
	public function getFormMessages();
}

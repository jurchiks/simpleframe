<?php
namespace responses;

interface Response
{
	/**
	 * Output the contents of the Response to stdout.
	 */
	public function render();
}

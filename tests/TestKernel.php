<?php

namespace MasterRO\LaravelFileCleaner\Tests;

class TestKernel extends \Orchestra\Testbench\Console\Kernel
{
	/**
	 * @param \Symfony\Component\Console\Command\Command $command
	 */
	public function registerCommand($command)
	{
		$this->getArtisan()->add($command);
	}
}
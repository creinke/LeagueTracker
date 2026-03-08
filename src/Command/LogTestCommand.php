<?php
namespace App\Command;

require __DIR__ . '/../../vendor/autoload.php';

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LogTestCommand extends Command {
	public function __construct(private readonly LoggerInterface $logger) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('app:log-test');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int	{
		$this->logger->debug('Standalone test debug message!');
		$this->logger->info('Standalone test info message.');
		$this->logger->debug('[doctrine] This goes to the Doctrine channel');

		$output->writeln('Logging complete.');

		return Command::SUCCESS;
	}
}

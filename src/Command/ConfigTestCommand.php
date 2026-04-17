<?php
namespace App\Command;

use App\Repository\LeagueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument as InputArgumentAlias;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:config-test',
    description: "Displays/dumps a league's configuration to the console."
)]
class ConfigTestCommand extends Command {
	public function __construct(private readonly EntityManagerInterface $em, private readonly LoggerInterface $logger) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('app:config-test');
		$this->addArgument('leagueName', InputArgumentAlias::REQUIRED, 'League Name');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int	{
        /** @noinspection DuplicatedCode */
        $appEnv = $_ENV['APP_ENV'] ?? ($_SERVER['APP_ENV'] ?? null);
		$databaseUrl = $_ENV['DATABASE_URL'] ?? ($_SERVER['DATABASE_URL'] ?? null);

		// Print these out so that you know what environment the application is running in.
		$output->writeln('APP_ENV: '.($appEnv ?? '(not set)'));
		$output->writeln('DATABASE_URL: '.($databaseUrl ?? '(not set)'));

		if ($input->getArguments() < 2 ) {
			exit( "Usage: app:config-test <league name>\n" );
		} else {
			$leagueName = $input->getArgument('leagueName');
		}

		$leagueRepository = new LeagueRepository($this->em, $this->logger);
		$league = $leagueRepository->findLeagueByName($leagueName);

		if ($league === null) {
			$output->writeln($leagueName . ' does not exist in this database!');
			return Command::FAILURE;
		}

		$output->writeln( 'League:');
		$output->writeln( "\t" . 'Name: ' . $league->getName() . '(' . $league->getId() . '):');

		$output->writeln( "\t\t" . 'Courses:');
		$courses = $league->getCourses();

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if ($courses === null) {
			$output->writeln("\t\t\t" . 'No courses associated with league!');
		} else {
			foreach ( $courses as $course ) {
				$address = $course->getAddress();
				$region  = $address->getRegion();

				$output->writeln("\t\t\t" . 'Name: ' . $course->getName() .
	                "\t\t\t" . 'Address: ' . $address->getAddressline1() . ' ' . $address->getAddressline2() . ' ' . $address->getCity() . ' ' . $region->getName() . ' ' . $region->getCountry()->getName() . ' ' . $address->getPostalcode() );
				$output->writeln( "\t\t\t" . 'Website: ' . $course->getWebsite());

				/*
				 * Course Nines
				 */
				$nines = $course->getNines();
				$output->writeln("\t\t\t" . 'Nines:');

				if ($nines === null) {
					$output->writeln("\t\t\t\t" . 'No nines associated with course!');
				} else {
					foreach ( $nines as $nine ) {
						$output->writeln("\t\t\t\t" . $nine->getName() . ':');

						/*
						 * Course Nine Tees
						 */
						$tees = $nine->getTees();
						$output->writeln("\t\t\t\t\t" . 'Tees:');

						if ($tees === null) {
							$output->writeln("\t\t\t\t\t\t" . 'No tees associated with this nine!');
						} else {
							foreach ($tees as $tee) {
								$output->writeln("\t\t\t\t\t\t" . $tee->getName() . ': ' .
 			                        'par=' . $tee->getPar() . ', slope=' . $tee->getSlope() . ', length=' . $tee->getLength() . ', rating=' . $tee->getRating());

								/*
								 * Course Nine Tee Holes
								 */
								$holes = $tee->getHoles();
								$output->writeln("\t\t\t\t\t\t\t" . 'Holes:');

								if ($holes === null) {
									$output->writeln("\t\t\t\t\t\t\t\t" . 'No holes associated with this tee!');
								} else {
									$iterator = $holes->getIterator();
									$iterator->uasort( function ( $a, $b ) {
										return ($a->getHolenumber() < $b->getHolenumber()) ? - 1 : 1;
									} );

									$holes = new ArrayCollection(iterator_to_array( $iterator ));

									foreach ($holes as $hole) {
										$output->writeln( "\t\t\t\t\t\t\t\t" . $hole->getName() . ': ' . 'par=' . $hole->getPar() . ', length=' . $hole->getLength() . ', handicap=' . $hole->getHandicap());
									}
								}
							}
						}
					}
				}
			}
		}
		$output->writeln( "\t\t" . 'Players:');
		$players = $league->getPlayers();

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if ($players === null) {
			$output->writeln("\t\t\t" . 'No players associated with this league!');
		} else {
			foreach ($players as $player) {
				$name = $player->getName();
				$output->writeln("\t\t\t" . 'Name: ' . $name->getFirstname() . ' ' . $name->getMiddlenameorinitial() . ' ' . $name->getLastname() . ' ' . $name->getGeneration());

				if (!empty( $player->getAddress())) {
					$address = $player->getAddress();
                    /** @noinspection PhpUndefinedVariableInspection */
                    $region->$address->getRegion();
					$output->writeln("\t\t\t\t" . 'Address: ' . $address->getAddressline1() . ' ' . $address->getAddressline2() . ' ' . $address->getCity() . ' ' . $region->getName() . ' ' . $region->getCountry()->getName() . ' ' . $address->getPostalcode());
				}
				if (!empty($player->getEmailAddresses())) {
					$output->writeln("\t\t\t\t" . 'Email Address: ');
					$emails = $player->getEmailAddresses();

					foreach ($emails as $email) {
						$output->writeln("\t\t\t\t\t" . $email->getAddress());
					}
				}
				if (!empty( $player->getPhonenumbers())) {
					$output->writeln("\t\t\t\t" . 'Phone Numbes: ');
					$phonenumbers = $player->getPhonenumbers();

					foreach ($phonenumbers as $phonenumber) {
						$output->writeln("\t\t\t\t\t" . $phonenumber->getNumber());
					}
				}
			}
		}

		$output->writeln("\t\t" . 'Teams:');
		$teams = $league->getTeams();

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if ($teams === null) {
			$output->writeln("\t\t\t" . 'No teams associated with this league!');
		} else {
			foreach ($teams as $team) {
				$output->writeln("\t\t\t" . 'Name: ' . $team->getName());
				$output->writeln("\t\t\t\t" . 'Team Number: ' . $team->getTeamnumber());
			}
		}

		$output->writeln( "\t\t" . 'Seasons:');
		$seasons = $league->getSeasons();

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if ($seasons === null) {
			$output->writeln("\t\t\t" . 'No seasons associated with this league!');
		} else {
			foreach ($seasons as $season) {
				$output->writeln( "\t\t\t" . 'Name: ' . $season->getName() .
				    ' Starting: ' . $season->getStartdate()->format( 'Y-m-d' ) . ' Ending: ' . $season->getEnddate()->format( 'Y-m-d' ) );

				$output->writeln( "\t\t\t" . 'Sessions:');
				$sessions = $season->getSessions();

				if ($sessions === null) {
					$output->writeln("\t\t\t\t" . 'No sessions associated with this season!');
				} else {
					foreach ($sessions as $session) {
						$output->writeln( "\t\t\t\t" . 'Name: ' . $session->getName() .
	                        ' Starting: ' . $session->getStartdate()->format( 'Y-m-d' ) . ' Ending: ' . $session->getEnddate()->format( 'Y-m-d' ) );

						$output->writeln( "\t\t\t\t" . 'Events:' );
						$events = $session->getEvents();

						if ( $events === null ) {
							$output->writeln( "\t\t\t\t\t" . 'No events associated with this season!' );
						} else {
							foreach ($events as $event) {
								$output->writeln( "\t\t\t\t\t" . 'Event #' . $event->getEventnumber() . ': ' .
								    'Starting: ' . $event->getStartdateandtime()->format('Y-m-d @ H:i'));

								if ($event->isTeamMatch($event->getEventtype())) {
									$games = $event->getGames();

									if ($games === null) {
										$output->writeln( "\t\t\t\t\t\t" . 'No games associated with this event!' );
									} else {
										$gameNumber = 0;
										foreach ($games as $game) {
											$teamMatches = $game->getTeammatches();
											$teamMatch = $teamMatches->first();
											$teamOne = $teamMatch->getTeamone();
											$teamTwo = $teamMatch->getTeamtwo();
											$match = $teamOne->getName() . ' vs ' . $teamTwo->getName();

											$output->writeln( "\t\t\t\t\t\t" . 'Game #' . ++ $gameNumber .
											    ' starting at ' . $game->getStartingtime()->format('H:i') . ': ' . $match);
										}
									}
								} else if ($event->isTeamEvent($event->getEventtype())) {
									$teamgames = $event->getTeamgames();

									if ($teamgames === null) {
										$output->writeln( "\t\t\t\t\t\t" . 'No games associated with this event!' );
									} else {
										$gameNumber = 0;
										foreach ($teamgames as $teamgame) {
											$teamOne = $teamgame->getTeamone();
											$teamTwo = $teamgame->getTeamtwo();
											$match = $teamOne . ' vs ' . $teamTwo;

											$output->writeln( "\t\t\t\t\t\t" . 'Game #' . ++ $gameNumber .
						                        ' starting at ' . $teamgame->getStartingtime()->format('H:i') . ': ' . $match);
										}
									}
								} else {
									$output->writeln( "\t\t\t\t\t\t" . 'Event type not recognised!' );
								}
							}
						}
					}
				}
			}
		}
		return Command::SUCCESS;
	}
}


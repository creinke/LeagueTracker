<?php
namespace App\Command;

use App\Repository\CountryRepository;
use App\Repository\CourseRepository;
use App\Repository\LeagueRepository;
use App\Repository\PlayerRepository;
use App\Repository\RegionRepository;
use App\Repository\SeasonRepository;
use App\Repository\TeamRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument as InputArgumentAlias;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
	name: 'app:league-loader',
	description: 'Imports a new league from a JSON formatted file'
)]
class LeagueLoaderCommand extends Command {
	public function __construct(private readonly EntityManagerInterface $em, private readonly LoggerInterface $logger, private readonly UserPasswordHasherInterface $passwordHasher) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('app:league-loader');
		$this->addArgument('leagueDataFile', InputArgumentAlias::REQUIRED, 'League Data File');
	}

	/**
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int	{
        /** @noinspection DuplicatedCode */
        $appEnv = $_ENV['APP_ENV'] ?? ($_SERVER['APP_ENV'] ?? null);
		$databaseUrl = $_ENV['DATABASE_URL'] ?? ($_SERVER['DATABASE_URL'] ?? null);

		// Print these out so that you know what environment the application is running in.
		$output->writeln('APP_ENV: '.($appEnv ?? '(not set)'));
		$output->writeln('DATABASE_URL: '.($databaseUrl ?? '(not set)'));

		if ($input->getArguments() < 2 ) {
			exit( "Usage: app:loader <league JSON file name>\n" );
		} else {
			$leagueDataFile = $input->getArgument('leagueDataFile');
		}
		$json = file_get_contents(__DIR__ . '/../../data/' . $leagueDataFile . '.json');

		// Decode JSON string into the associative data array $d
		$d = json_decode($json, true);

		if (json_last_error()) {
			exit ('Error detected in file ' . $leagueDataFile . ': ' . json_last_error_msg());
		}

		$leagueRepository = new LeagueRepository($this->em, $this->logger);

		$leagueName = $d['name'];
		if ($leagueRepository->findLeagueByName($leagueName)) {
			$output->writeln($leagueName . ' already exists!');
			return Command::FAILURE;
		}

        if (isset($d['country'])) {
            // Create the Countries in the database needed by other pertinant entities.
            $countryRepository = new CountryRepository($this->em, $this->logger);
            /** @noinspection PhpUnusedLocalVariableInspection */
            $countries = $countryRepository->saveAll($d['country']);

            // Create all the regions in the database associated with these countries.
            $regionRepository = new RegionRepository($this->em, $this->logger);

            foreach ($d['country'] as $country) {
                $name = $country['iso3'];
                $c = $countryRepository->findCountryByName($name);
                /** @noinspection PhpUnusedLocalVariableInspection */
                $regions = $regionRepository->saveAll($c, $country['region']);
		    }
        }

		$leagueData = array();
		$leagueData['name'] = $d['name'];

        if (isset($d['course'])) {
            $courseRepository = new CourseRepository($this->em, $this->logger);
            $courses = $courseRepository->saveAll($d['course']);
            $leagueData['courses'] = $courses;
        }

        if (isset($d['user'])) {
            $league = $leagueRepository->save($leagueData);

            $userRepository = new UserRepository($this->em, $this->logger, $this->passwordHasher);
            $usersData = $d['user'];
            $users = $userRepository->saveAll($usersData, $league);
            $league->setUsers($users);
        } else {
            exit('Error detected in file ' . $leagueDataFile . ': The JSON user element is required');
        }

        if (isset($d['player'])) {
            $playerRepository = new PlayerRepository($this->em, $this->logger);
            $playersData = $d['player'];
            $players = $playerRepository->saveAll($playersData, $league);
            $league->setPlayers($players);
        }

        if (isset($d['team'])) {
            $teamRepository = new TeamRepository($this->em, $this->logger);
            $teamsData = $d['team'];
            $teams = $teamRepository->saveAll($teamsData, $league);
            $league->setTeams($teams);
        }

        if (isset($d['season'])) {
            $seasonRepository = new SeasonRepository($this->em, $this->logger);
            $seasonsData = $d['season'];
            $seasons = $seasonRepository->saveAll($seasonsData, $league);
            $league->setSeasons($seasons);
        }

		$output->writeln('League Loader complete.');
		return Command::SUCCESS;
	}
}

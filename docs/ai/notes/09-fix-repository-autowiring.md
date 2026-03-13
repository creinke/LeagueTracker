### Repository Autowiring Fix Summary

Fixed the `RuntimeException` caused by incompatible `__construct()` methods in the repository layer that prevented Symfony from autowiring `ClassMetadata`.

#### **1. Problem Identified**
- The `AbstractBaseRepository` and its subclasses had a constructor that required `ClassMetadata $class`.
- Symfony's autowiring cannot provide `ClassMetadata` directly because it's not a standard service.
- This caused HTTP 500 errors when attempting to access controllers or run tests that depend on these repositories.

#### **2. Changes Implemented**

- **`AbstractBaseRepository` Refactoring**:
    - Changed the constructor to take `EntityManagerInterface $em`, `LoggerInterface $logger`, and `string $entityClass`.
    - It now uses `$em->getClassMetadata($entityClass)` internally to satisfy the parent `EntityRepository` constructor.
- **Subclass Updates**:
    - Updated `UserRepository`, `PlayerRepository`, `SeasonRepository`, `EventRepository`, `LeagueRepository`, `SessionRepository`, `CourseRepository`, and `CountryRepository` to match the new constructor.
    - Each subclass now correctly passes its corresponding entity class name to the parent constructor.
- **`config/services.yaml` Update**:
    - Enabled autowiring and autoconfiguration for all repositories in `App\Repository\`.
    - Added the `doctrine.repository_service` tag to ensure they are correctly registered as repository services.
- **Bug Fix**:
    - Updated `SeasonRepository::findById` to return a nullable `?SeasonDE` instead of a strict `SeasonDE`, allowing for proper 404 responses when a season is not found.

#### **3. Verification Performed**
- Verified the container configuration using `php bin/console debug:container "App\Repository\UserRepository"`.
- Successfully ran functional API tests:
    - `ApiSecurityControllerTest`: **Passed** (2 tests, 9 assertions)
    - `ApiPlayerControllerTest`: **Passed** (4 tests, 20 assertions)
    - `ApiSeasonControllerTest`: **Passed** (3 tests, 15 assertions)
- Confirmed that the web application no longer reports the `ClassMetadata` autowiring exception.

### Issue Fixed: Missing `api_token` Column in User Table

A database error occurred when browsing to the login screen after Phase 1 implementation. This was caused by the `api_token` column being added to the `UserDE` entity but not yet existing in the physical database schema.

#### **Symptoms**
- `PDOException`: `SQLSTATE[42S22]: Column not found: 1054 Unknown column 't0.api_token' in 'field list'`
- Occurred during any operation that fetched a `User` entity (e.g., login, home page).

#### **Fix**
Manually applied the missing schema changes to the `user` table for both production and test environments. The `doctrine:schema:update --force` command could not be used directly because of unrelated foreign key issues in other parts of the legacy schema.

**SQL Applied:**
```sql
ALTER TABLE user ADD api_token VARCHAR(255) DEFAULT NULL;
CREATE UNIQUE INDEX UNIQ_8D93D6497BA2F5EB ON user (api_token);
```

#### **Verification**
- **Manual Verification**: Ran `php bin/console doctrine:query:sql "SELECT id, username, api_token FROM user LIMIT 1"` to confirm column existence.
- **Automated Verification**:
    - `tests/Controller/HomeControllerTest.php`: PASSED (6 tests).
    - `tests/Controller/UserControllerTest.php`: `InvalidFieldNameException` resolved; remaining failure is unrelated to this change.

#### **Environment Details**
- **Host**: `mysql.premiersportssimulators.com`
- **Database**: `premiergolfleaguetracker`

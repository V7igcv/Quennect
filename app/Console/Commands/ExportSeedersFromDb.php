<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ExportSeedersFromDb extends Command
{
    protected $signature = 'db:seed:export {--tables= : Comma-separated list of tables to export}';

    protected $description = 'Export selected tables into idempotent seeders (updateOrInsert).';

    public function handle()
    {
        $defaultTables = [
            'assistance_types',
            'evaluation_questions',
            'roles',
            'users',
            'services',
            'offices',
        ];

        $tablesOption = $this->option('tables');
        $tables = $tablesOption ? array_map('trim', explode(',', $tablesOption)) : $defaultTables;

        $this->info('Exporting tables: ' . implode(', ', $tables));

        $generated = [];

        foreach ($tables as $table) {
            switch ($table) {
                case 'roles':
                    $this->exportRoles();
                    $generated[] = 'RoleSeeder';
                    break;
                case 'evaluation_questions':
                    $this->exportEvaluationQuestions();
                    $generated[] = 'EvaluationQuestionSeeder';
                    break;
                case 'assistance_types':
                    $this->exportAssistanceTypes();
                    $generated[] = 'AssistanceTypeSeeder';
                    break;
                case 'offices':
                    $this->exportOffices();
                    $generated[] = 'OfficeSeeder';
                    break;
                case 'services':
                    $this->exportServices();
                    $generated[] = 'ServiceSeeder';
                    break;
                case 'users':
                    $this->exportUsers();
                    $generated[] = 'UserSeeder';
                    break;
                default:
                    $this->warn("Skipping unsupported table: $table");
                    break;
            }
        }

        $this->updateDatabaseSeeder($generated);

        $this->info('Seeder files generated in database/seeders. Review and commit them.');
        $this->info('You can then run: php artisan db:seed');

        return 0;
    }

    protected function exportRoles()
    {
        $rows = DB::table('roles')->get();

        $entries = [];
        foreach ($rows as $r) {
            $entries[] = [
                'name' => $r->name,
                'created_at' => $r->created_at ? $r->created_at : now(),
                'updated_at' => $r->updated_at ? $r->updated_at : now(),
            ];
        }

        $class = 'RoleSeeder';
        $content = $this->renderSeederClass($class, 'roles', $entries, "['name']");
        File::put(database_path('seeders/' . $class . '.php'), $content);
        $this->info("Exported roles: " . count($entries));
    }

    protected function exportEvaluationQuestions()
    {
        $rows = DB::table('evaluation_questions')->get();
        $entries = [];
        foreach ($rows as $r) {
            $entries[] = [
                'question_text' => $r->question_text,
                'question_type' => $r->question_type,
                'created_at' => $r->created_at ? $r->created_at : now(),
                'updated_at' => $r->updated_at ? $r->updated_at : now(),
            ];
        }

        $class = 'EvaluationQuestionSeeder';
        $content = $this->renderSeederClass($class, 'evaluation_questions', $entries, "['question_text']");
        File::put(database_path('seeders/' . $class . '.php'), $content);
        $this->info("Exported evaluation_questions: " . count($entries));
    }

    protected function exportAssistanceTypes()
    {
        // join services to get service_code
        $rows = DB::table('assistance_types')
            ->leftJoin('services', 'assistance_types.service_id', '=', 'services.id')
            ->select('assistance_types.*', 'services.service_code')
            ->get();

        $entries = [];
        foreach ($rows as $r) {
            $entries[] = [
                'assistance_name' => $r->assistance_name,
                'service_code' => $r->service_code,
                'created_at' => $r->created_at ? $r->created_at : now(),
                'updated_at' => $r->updated_at ? $r->updated_at : now(),
            ];
        }

        $class = 'AssistanceTypeSeeder';
        $content = $this->renderAssistanceTypeSeeder($class, $entries);
        File::put(database_path('seeders/' . $class . '.php'), $content);
        $this->info("Exported assistance_types: " . count($entries));
    }

    protected function exportOffices()
    {
        $rows = DB::table('offices')->get();
        $entries = [];
        foreach ($rows as $r) {
            $entries[] = [
                'office_name' => $r->office_name,
                'office_description' => $r->office_description,
                'office_acronym' => $r->office_acronym,
                'logo' => $r->logo,
                'is_active' => (bool) $r->is_active,
                'created_at' => $r->created_at ? $r->created_at : now(),
                'updated_at' => $r->updated_at ? $r->updated_at : now(),
            ];
        }

        $class = 'OfficeSeeder';
        $content = $this->renderSeederClass($class, 'offices', $entries, "['office_acronym']");
        File::put(database_path('seeders/' . $class . '.php'), $content);
        $this->info("Exported offices: " . count($entries));
    }

    protected function exportServices()
    {
        $rows = DB::table('services')
            ->leftJoin('offices', 'services.office_id', '=', 'offices.id')
            ->select('services.*', 'offices.office_acronym')
            ->get();

        $entries = [];
        foreach ($rows as $r) {
            $entries[] = [
                'service_name' => $r->service_name,
                'service_code' => $r->service_code,
                'service_description' => $r->service_description,
                'office_acronym' => $r->office_acronym,
                'created_at' => $r->created_at ? $r->created_at : now(),
                'updated_at' => $r->updated_at ? $r->updated_at : now(),
            ];
        }

        $class = 'ServiceSeeder';
        $content = $this->renderServiceSeeder($class, $entries);
        File::put(database_path('seeders/' . $class . '.php'), $content);
        $this->info("Exported services: " . count($entries));
    }

    protected function exportUsers()
    {
        $rows = DB::table('users')
            ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
            ->leftJoin('offices', 'users.office_id', '=', 'offices.id')
            ->select('users.*', 'roles.name as role_name', 'offices.office_acronym as office_acronym')
            ->get();

        $entries = [];
        foreach ($rows as $r) {
            $entries[] = [
                'username' => $r->username,
                'password_hash' => $r->password_hash,
                'role_name' => $r->role_name,
                'office_acronym' => $r->office_acronym,
                'last_login_at' => $r->last_login_at,
                'created_at' => $r->created_at ? $r->created_at : now(),
                'updated_at' => $r->updated_at ? $r->updated_at : now(),
            ];
        }

        $class = 'UserSeeder';
        $content = $this->renderUserSeeder($class, $entries);
        File::put(database_path('seeders/' . $class . '.php'), $content);
        $this->info("Exported users: " . count($entries));
    }

    protected function renderSeederClass($className, $tableName, array $entries, $keyExpression)
    {
        $rowsCode = '';
        foreach ($entries as $e) {
            $export = var_export($e, true);
            $rowsCode .= "\n            DB::table('$tableName')->updateOrInsert($keyExpression, $export);\n\n";
        }

        return <<<PHP
<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class $className extends Seeder
{
    public function run(): void
    {
$rowsCode    }
}
PHP;
    }

    protected function renderAssistanceTypeSeeder($className, array $entries)
    {
        $rowsCode = '';
        foreach ($entries as $e) {
            $assistance = var_export([
                'assistance_name' => $e['assistance_name'],
                'service_code' => $e['service_code'],
                'created_at' => $e['created_at'],
                'updated_at' => $e['updated_at'],
            ], true);

            $rowsCode .= "\n            // assistance: " . addslashes($e['assistance_name']) . "\n";
            $rowsCode .= "            DB::table('assistance_types')->updateOrInsert([ 'assistance_name' => '" . addslashes($e['assistance_name']) . "' ], [ 'service_id' => DB::table('services')->where('service_code', '" . addslashes($e['service_code']) . "')->value('id'), 'assistance_name' => '" . addslashes($e['assistance_name']) . "', 'created_at' => '" . $e['created_at'] . "', 'updated_at' => '" . $e['updated_at'] . "' ]);\n\n";
        }

        return <<<PHP
<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class $className extends Seeder
{
    public function run(): void
    {
$rowsCode    }
}
PHP;
    }

    protected function renderServiceSeeder($className, array $entries)
    {
        $rowsCode = '';
        foreach ($entries as $e) {
            $rowsCode .= "\n            DB::table('services')->updateOrInsert( [ 'office_id' => DB::table('offices')->where('office_acronym', '" . addslashes($e['office_acronym']) . "')->value('id'), 'service_code' => '" . addslashes($e['service_code']) . "' ], [ 'service_name' => '" . addslashes($e['service_name']) . "', 'service_description' => '" . addslashes($e['service_description']) . "', 'office_id' => DB::table('offices')->where('office_acronym', '" . addslashes($e['office_acronym']) . "')->value('id'), 'created_at' => '" . $e['created_at'] . "', 'updated_at' => '" . $e['updated_at'] . "' ]);\n\n";
        }

        return <<<PHP
<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class $className extends Seeder
{
    public function run(): void
    {
$rowsCode    }
}
PHP;
    }

    protected function renderUserSeeder($className, array $entries)
    {
        $rowsCode = '';
        foreach ($entries as $e) {
            $username = addslashes($e['username']);
            $password = addslashes($e['password_hash']);
            $roleName = addslashes($e['role_name']);
            $officeAcr = $e['office_acronym'] ? addslashes($e['office_acronym']) : null;
            $lastLogin = $e['last_login_at'] !== null ? $e['last_login_at'] : 'null';
            $createdAt = $e['created_at'];
            $updatedAt = $e['updated_at'];

            $officeLookup = $officeAcr ? "DB::table('offices')->where('office_acronym', '$officeAcr')->value('id')" : 'null';

            $rowsCode .= "\n            DB::table('users')->updateOrInsert( [ 'username' => '$username' ], [ 'password_hash' => '$password', 'role_id' => DB::table('roles')->where('name', '$roleName')->value('id'), 'office_id' => $officeLookup, 'last_login_at' => ".($lastLogin==='null'?'null':"'$lastLogin'").", 'created_at' => '$createdAt', 'updated_at' => '$updatedAt' ]);\n\n";
        }

        return <<<PHP
<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class $className extends Seeder
{
    public function run(): void
    {
$rowsCode    }
}
PHP;
    }

    protected function updateDatabaseSeeder(array $generated)
    {
        $dbSeederPath = database_path('seeders/DatabaseSeeder.php');
        if (!File::exists($dbSeederPath)) {
            $this->warn('DatabaseSeeder.php not found; skipping update.');
            return;
        }

        $content = File::get($dbSeederPath);

        // Build new call array content
        $calls = [];
        $calls[] = "BarangaySeeder::class"; // keep barangay
        $calls[] = "PrioritySectorSeeder::class"; // keep priority sectors
        foreach ($generated as $g) {
            $calls[] = $g . "::class";
        }

        $newCall = "\t\t\t\t\$this->call([\n";
        foreach ($calls as $c) {
            $newCall .= "\t\t\t\t\t$c,\n";
        }
        $newCall .= "\t\t\t\t]);";

        // Replace existing $this->call([...]); block roughly
        $content = preg_replace("/\$this->call\(\s*\[[\s\S]*?\]\s*\)\s*;/", $newCall, $content);

        File::put($dbSeederPath, $content);
        $this->info('Updated DatabaseSeeder.php to call generated seeders.');
    }
}

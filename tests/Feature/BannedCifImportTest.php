<?php

namespace Tests\Feature;

use App\Jobs\Batches\ProcessCaenCompanyImportJob;
use App\Jobs\Batches\ProcessCompanyBatchImportJobUpsert;
use App\Jobs\Batches\ProcessEUBranchBatchImport;
use App\Jobs\Batches\ProcessLegalRepresentativeBatchImport;
use App\Jobs\Batches\ProcessNaturalPersonRepresentativeBatchImport;
use App\Jobs\Batches\ProcessStatusBatchImportJob;
use App\Models\CaenCompany;
use App\Models\Company;
use App\Models\EUBranch;
use App\Models\LegalRepresentative;
use App\Models\NaturalPersonRepresentative;
use App\Models\Status;
use App\Services\BannedCifService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BannedCifImportTest extends TestCase
{
    use RefreshDatabase;

    private const BANNED_CUI = '52491237';

    private const BANNED_REG_COM = 'J40/9999/2024';

    private const ALLOWED_CUI = '123456789';

    private const ALLOWED_REG_COM = 'J40/1234/2024';

    protected function setUp(): void
    {
        parent::setUp();

        config(['skip.cifs' => [self::BANNED_CUI]]);
    }

    public function test_banned_cif_is_not_imported_as_a_company(): void
    {
        $this->assertTrue(BannedCifService::isBanned(self::BANNED_CUI));
        $this->assertFalse(BannedCifService::isBanned(self::ALLOWED_CUI));

        $this->dispatchCompanyBatch([
            $this->companyRow(self::BANNED_CUI, self::BANNED_REG_COM),
            $this->companyRow(self::ALLOWED_CUI, self::ALLOWED_REG_COM),
        ]);

        $this->assertDatabaseMissing('companies', ['cui' => self::BANNED_CUI]);
        $this->assertDatabaseHas('companies', ['cui' => self::ALLOWED_CUI]);
    }

    public function test_statuses_for_unknown_or_banned_companies_are_skipped(): void
    {
        $this->dispatchCompanyBatch([$this->companyRow(self::ALLOWED_CUI, self::ALLOWED_REG_COM)]);

        (new ProcessStatusBatchImportJob([
            [self::ALLOWED_REG_COM, 1048],
            [self::BANNED_REG_COM, 1049],
        ], ['COD_INMATRICULARE' => 0, 'COD' => 1], 1))->handle();

        $this->assertSame(1, Status::count());
        $this->assertSame(self::ALLOWED_REG_COM, Status::first()->registration);
    }

    public function test_statuses_are_not_duplicated_when_reimported(): void
    {
        $this->dispatchCompanyBatch([$this->companyRow(self::ALLOWED_CUI, self::ALLOWED_REG_COM)]);

        $statusJob = new ProcessStatusBatchImportJob(
            [[self::ALLOWED_REG_COM, 1048]],
            ['COD_INMATRICULARE' => 0, 'COD' => 1],
            1
        );

        $statusJob->handle();
        $statusJob->handle();

        $this->assertSame(1, Status::count());
    }

    public function test_legal_representatives_are_linked_and_skipped_for_banned_companies(): void
    {
        $this->dispatchCompanyBatch([$this->companyRow(self::ALLOWED_CUI, self::ALLOWED_REG_COM)]);
        $company = Company::first();

        $fieldMap = [
            'COD_INMATRICULARE' => 0,
            'PERSOANA_IMPUTERNICITA' => 1,
            'CALITATE' => 2,
            'DATA_NASTERE' => 3,
            'LOCALITATE_NASTERE' => 4,
            'JUDET_NASTERE' => 5,
            'TARA_NASTERE' => 6,
            'LOCALITATE' => 7,
            'JUDET' => 8,
            'TARA' => 9,
        ];

        $job = new ProcessLegalRepresentativeBatchImport([
            [self::ALLOWED_REG_COM, 'POPESCU ION', 'administrator', '01/01/1980', 'Cluj', 'Cluj', 'România', 'Cluj', 'Cluj', 'România'],
            [self::BANNED_REG_COM, 'BANNED PERSON', 'administrator', '01/01/1980', '', '', '', '', '', ''],
        ], $fieldMap, 1);

        $job->handle();

        $this->assertSame(1, LegalRepresentative::count());
        $this->assertSame($company->id, LegalRepresentative::first()->company_id);
        $this->assertDatabaseMissing('legal_representatives', ['person_name' => 'BANNED PERSON']);
    }

    public function test_natural_person_representatives_are_skipped_for_banned_companies(): void
    {
        $this->dispatchCompanyBatch([$this->companyRow(self::ALLOWED_CUI, self::ALLOWED_REG_COM)]);

        $fieldMap = [
            'COD_INMATRICULARE' => 0,
            'NUME' => 1,
            'DATA_NASTERE' => 2,
            'LOCALITATE_NASTERE' => 3,
            'JUDET_NASTERE' => 4,
            'TARA_NASTERE' => 5,
            'CALITATE' => 6,
        ];

        (new ProcessNaturalPersonRepresentativeBatchImport([
            [self::ALLOWED_REG_COM, 'IONESCU ANA', '01/01/1980', 'Cluj', 'Cluj', 'România', 'membru'],
            [self::BANNED_REG_COM, 'BANNED NATURAL', '01/01/1980', '', '', '', 'membru'],
        ], $fieldMap, 1))->handle();

        $this->assertSame(1, NaturalPersonRepresentative::count());
        $this->assertDatabaseMissing('natural_person_representatives', ['full_name' => 'BANNED NATURAL']);
    }

    public function test_eu_branches_are_skipped_for_banned_companies(): void
    {
        $this->dispatchCompanyBatch([$this->companyRow(self::ALLOWED_CUI, self::ALLOWED_REG_COM)]);

        $fieldMap = [
            'COD_INMATRICULARE' => 0,
            'TIP_UNITATE' => 1,
            'DENUMIRE_SUCURSALA' => 2,
            'EUID' => 3,
            'COD_FISCAL' => 4,
            'TARA' => 5,
        ];

        (new ProcessEUBranchBatchImport([
            [self::ALLOWED_REG_COM, 'Sucursală', 'ALLOWED BRANCH', '', '', 'Italia'],
            [self::BANNED_REG_COM, 'Sucursală', 'BANNED BRANCH', '', '', 'Germania'],
        ], $fieldMap, 1))->handle();

        $this->assertSame(1, EUBranch::count());
        $this->assertDatabaseMissing('e_u_branches', ['branch_name' => 'BANNED BRANCH']);
    }

    public function test_caen_links_are_skipped_for_banned_companies(): void
    {
        $this->dispatchCompanyBatch([$this->companyRow(self::ALLOWED_CUI, self::ALLOWED_REG_COM)]);

        $fieldMap = [
            'COD_INMATRICULARE' => 0,
            'COD_CAEN_AUTORIZAT' => 1,
            'VER_CAEN_AUTORIZAT' => 2,
        ];

        (new ProcessCaenCompanyImportJob([
            [self::ALLOWED_REG_COM, '6201', '2'],
            [self::ALLOWED_REG_COM, '6201', '2'],
            [self::BANNED_REG_COM, '6201', '2'],
        ], $fieldMap))->handle();

        $this->assertSame(1, CaenCompany::count());
        $this->assertSame(self::ALLOWED_REG_COM, CaenCompany::first()->registration);
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function dispatchCompanyBatch(array $rows): void
    {
        $fieldMap = [
            'DENUMIRE' => 0,
            'CUI' => 1,
            'COD_INMATRICULARE' => 2,
            'DATA_INMATRICULARE' => 3,
            'EUID' => 4,
            'FORMA_JURIDICA' => 5,
            'ADR_TARA' => 6,
            'ADR_JUDET' => 7,
            'ADR_LOCALITATE' => 8,
            'ADR_DEN_STRADA' => 9,
            'ADR_NR_STRADA' => 10,
            'ADR_BLOC' => 11,
            'ADR_SCARA' => 12,
            'ADR_ETAJ' => 13,
            'ADR_APARTAMENT' => 14,
            'ADR_COD_POSTAL' => 15,
            'ADR_SECTOR' => 16,
            'ADR_COMPLETARE' => 17,
            'WEB' => 18,
            'TARA_FIRMA_MAMA' => 19,
            'MARK' => 20,
        ];

        (new ProcessCompanyBatchImportJobUpsert($rows, $fieldMap))->handle();
    }

    /**
     * @return array<int, string>
     */
    private function companyRow(string $cui, string $regCom): array
    {
        return [
            'Test Company SRL',
            $cui,
            $regCom,
            '01/01/2024',
            'ROONRC.'.$regCom,
            'SRL',
            'România',
            'Bucureşti',
            'Bucureşti Sectorul 1',
            'Str. Test',
            '1',
            '',
            '',
            '',
            '',
            '010001',
            '1',
            '',
            '',
            '',
            '',
        ];
    }

    protected function tearDown(): void
    {
        DB::table('caen_companies')->delete();

        parent::tearDown();
    }
}

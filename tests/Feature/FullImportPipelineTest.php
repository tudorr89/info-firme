<?php

namespace Tests\Feature;

use App\Models\CaenCompany;
use App\Models\Company;
use App\Models\EUBranch;
use App\Models\LegalRepresentative;
use App\Models\NaturalPersonRepresentative;
use App\Models\Status;
use App\Services\CompanyLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Runs the whole import pipeline end to end on small CSV fixtures that mirror
 * the ONRC file layout (including the UTF-8 BOM used by the published files).
 */
class FullImportPipelineTest extends TestCase
{
    use RefreshDatabase;

    private const BANNED_CUI = '52491237';

    private const BANNED_REG_COM = 'J40/9999/2024';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        config([
            'import.disk' => 'local',
            'import.directory' => 'imports',
            'skip.cifs' => [self::BANNED_CUI],
        ]);

        $this->writeFixtures();
    }

    public function test_the_full_pipeline_imports_and_links_every_file(): void
    {
        $this->artisan('import:all', ['--link-delay' => 0])->assertSuccessful();

        $company = Company::where('cui', '123456789')->first();

        $this->assertNotNull($company);
        $this->assertSame('J40/1234/2024', $company->reg_com);
        $this->assertSame('TEST COMPANY SRL', $company->name);
        $this->assertSame('www.example.com', $company->website);

        $this->assertDatabaseMissing('companies', ['cui' => self::BANNED_CUI]);

        $this->assertDatabaseHas('addresses', [
            'company_id' => $company->id,
            'city' => 'București',
            'street' => 'Str. Exemplu',
            'number' => '10',
        ]);

        $this->assertDatabaseHas('statuses', ['registration' => 'J40/1234/2024', 'status' => 1048]);
        $this->assertDatabaseMissing('statuses', ['registration' => self::BANNED_REG_COM]);

        $this->assertDatabaseHas('caen_companies', ['registration' => 'J40/1234/2024', 'code' => 6201]);
        $this->assertDatabaseMissing('caen_companies', ['registration' => self::BANNED_REG_COM]);

        $this->assertDatabaseHas('legal_representatives', [
            'registration' => 'J40/1234/2024',
            'company_id' => $company->id,
            'person_name' => 'POPESCU ION',
        ]);

        $this->assertDatabaseHas('natural_person_representatives', [
            'registration' => 'J40/1234/2024',
            'company_id' => $company->id,
            'full_name' => 'IONESCU ANA',
        ]);

        $this->assertDatabaseHas('e_u_branches', [
            'registration' => 'J40/1234/2024',
            'company_id' => $company->id,
            'branch_name' => 'SUCURSALA ROMA',
        ]);

        $this->assertDatabaseHas('nomenclators', ['code' => 1048, 'description' => 'funcțiune']);
        $this->assertDatabaseHas('caen_versions', ['code' => 2, 'name' => 'Versiunea 2008']);
        $this->assertDatabaseHas('caens', ['class' => 6201, 'version' => 2]);
    }

    public function test_the_link_backfill_repairs_legacy_rows_without_a_company_id(): void
    {
        Storage::disk('local')->delete('imports/od_firme.csv');

        $this->artisan('import:all', ['--link-delay' => 0])->assertSuccessful();

        $this->assertSame(0, Company::count());

        // Simulate rows written by an older importer that could not resolve the company yet.
        LegalRepresentative::create(['registration' => 'J40/1234/2024', 'person_name' => 'POPESCU ION']);
        NaturalPersonRepresentative::create(['registration' => 'J40/1234/2024', 'full_name' => 'IONESCU ANA']);
        EUBranch::create(['registration' => 'J40/1234/2024', 'branch_name' => 'SUCURSALA ROMA']);

        $this->assertSame(0, LegalRepresentative::whereNotNull('company_id')->count());

        $this->writeFile('imports/od_firme.csv', $this->companyHeader(), $this->companyRows());
        $this->artisan('import:companies', ['file' => Storage::disk('local')->path('imports/od_firme.csv')])
            ->assertSuccessful();

        (new CompanyLinkService)->linkAll();

        $company = Company::first();

        $this->assertNotNull($company);
        $this->assertSame(1, LegalRepresentative::where('company_id', $company->id)->count());
        $this->assertSame(1, NaturalPersonRepresentative::where('company_id', $company->id)->count());
        $this->assertSame(1, EUBranch::where('company_id', $company->id)->count());
        $this->assertSame(0, LegalRepresentative::whereNull('company_id')->count());
    }

    public function test_pruning_removes_data_of_banned_companies(): void
    {
        $this->writeFile(
            'imports/od_reprezentanti_legali.csv',
            'COD_INMATRICULARE^PERSOANA_IMPUTERNICITA^CALITATE^DATA_NASTERE^LOCALITATE_NASTERE^JUDET_NASTERE^TARA_NASTERE^LOCALITATE^JUDET^TARA',
            [self::BANNED_REG_COM.'^BANNED PERSON^administrator^^^^^^^']
        );

        (new CompanyLinkService)->pruneOrphans();

        $this->assertSame(0, LegalRepresentative::count());
        $this->assertSame(0, Status::count());
        $this->assertSame(0, CaenCompany::count());
    }

    private function writeFixtures(): void
    {
        $this->writeFile('imports/od_firme.csv', $this->companyHeader(), $this->companyRows());

        $this->writeFile(
            'imports/od_stare_firma.csv',
            'COD_INMATRICULARE^COD',
            ['J40/1234/2024^1048', self::BANNED_REG_COM.'^1049']
        );

        $this->writeFile(
            'imports/od_reprezentanti_legali.csv',
            'COD_INMATRICULARE^PERSOANA_IMPUTERNICITA^CALITATE^DATA_NASTERE^LOCALITATE_NASTERE^JUDET_NASTERE^TARA_NASTERE^LOCALITATE^JUDET^TARA',
            ['J40/1234/2024^POPESCU ION^administrator^01/01/1980^Cluj^Cluj^România^Cluj^Cluj^România']
        );

        $this->writeFile(
            'imports/od_reprezentanti_if.csv',
            'COD_INMATRICULARE^NUME^DATA_NASTERE^LOCALITATE_NASTERE^JUDET_NASTERE^TARA_NASTERE^CALITATE',
            ['J40/1234/2024^IONESCU ANA^01/01/1980^Cluj^Cluj^România^membru']
        );

        $this->writeFile(
            'imports/od_sucursale_alte_state_membre.csv',
            'COD_INMATRICULARE^TIP_UNITATE^DENUMIRE_SUCURSALA^EUID^COD_FISCAL^TARA',
            ['J40/1234/2024^Sucursală^SUCURSALA ROMA^^^Italia']
        );

        $this->writeFile(
            'imports/od_caen_autorizat.csv',
            'COD_INMATRICULARE^COD_CAEN_AUTORIZAT^VER_CAEN_AUTORIZAT',
            ['J40/1234/2024^6201^2', self::BANNED_REG_COM.'^6201^2']
        );

        $this->writeFile('imports/n_caen.csv', 'SECTIUNEA^SUBSECTIUNEA^DIVIZIUNEA^GRUPA^CLASA^DENUMIRE^VERSIUNE_CAEN', [
            'J^^62^620^6201^Activitati de realizare a soft-ului^2',
            'J^^62^620^6202^Alte activitati de servicii informatice^2',
        ]);

        $this->writeFile('imports/n_versiune_caen.csv', 'COD^DESCRIERE', ['2^Versiunea 2008']);

        $this->writeFile('imports/n_stare_firma.csv', 'COD^DENUMIRE', ['1048^funcțiune', '1049^dizolvare']);
    }

    private function companyHeader(): string
    {
        return 'DENUMIRE^CUI^COD_INMATRICULARE^DATA_INMATRICULARE^EUID^FORMA_JURIDICA^ADR_TARA^ADR_JUDET'
            .'^ADR_LOCALITATE^ADR_DEN_STRADA^ADR_NR_STRADA^ADR_BLOC^ADR_SCARA^ADR_ETAJ^ADR_APARTAMENT'
            .'^ADR_COD_POSTAL^ADR_SECTOR^ADR_COMPLETARE^WEB^TARA_FIRMA_MAMA';
    }

    /**
     * @return array<int, string>
     */
    private function companyRows(): array
    {
        return [
            'TEST COMPANY SRL^123456789^J40/1234/2024^01/01/2024^ROONRC.J40/1234/2024^SRL^România^Bucureşti'
            .'^București^Str. Exemplu^10^^^^^010001^1^^www.example.com^^',
            'BANNED COMPANY SRL^'.self::BANNED_CUI.'^'.self::BANNED_REG_COM
            .'^01/01/2024^ROONRC.'.self::BANNED_REG_COM.'^SRL^România^Bucureşti^București^Str. Interzisă'
            .'^1^^^^^010001^1^^^^',
        ];
    }

    /**
     * @param  array<int, string>  $rows
     */
    private function writeFile(string $path, string $header, array $rows): void
    {
        $contents = "\xEF\xBB\xBF".$header."\n".implode("\n", $rows)."\n";

        Storage::disk('local')->put($path, $contents);
    }
}

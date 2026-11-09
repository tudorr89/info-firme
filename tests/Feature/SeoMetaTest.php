<?php

namespace Tests\Feature;

use App\Models\Company;
use Tests\TestCase;

class SeoMetaTest extends TestCase
{
    public function test_a_company_name_cannot_break_out_of_the_json_ld_script(): void
    {
        $company = new Company([
            'name' => 'EVIL </script><script>alert(1)</script> SRL',
            'cui' => '12345678',
            'reg_com' => 'J40/1/2024',
        ]);

        // The relations are not needed here, and setting them keeps the
        // component from querying for them.
        $company->setRelation('address', null);
        $company->setRelation('info', null);

        $html = view('components.seo-meta', [
            'type' => 'organization',
            'company' => $company,
        ])->render();

        $this->assertStringNotContainsString('</script><script>alert', $html);
        $this->assertStringContainsString('\u003C/script\u003E', $html);
    }
}

<?php

namespace Tests\Unit;

use App\Services\SuggestionProviders\AcronymFinderProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Assert;
use Tests\TestCase;

class AcronymFinderProviderTest extends TestCase
{
    private AcronymFinderProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new AcronymFinderProvider;
    }

    public function test_get_provider_name()
    {
        $this->assertEquals('AcronymFinder', $this->provider->getProviderName());
    }

    public function test_get_suggestions_successful_acronym_finder()
    {
        $mockHtml = '<html>
            <td class="meaning">Application Programming Interface</td>
            <td class="meaning">Advanced Persistent Instruction</td>
            <td class="meaning">API Gateway Service</td>
        </html>';

        Http::fake([
            'www.acronymfinder.com/*' => Http::response($mockHtml, 200),
        ]);

        Log::shouldReceive('info')->atLeast()->once();

        $result = $this->provider->getSuggestions('API');

        $this->assertNotEmpty($result);
        $this->assertLessThanOrEqual(3, count($result));
        $this->assertEquals('AcronymFinder', $result[0]['source']);
        $this->assertEquals('english_meaning', $result[0]['type']);
        $this->assertArrayHasKey('original_meaning', $result[0]);
    }

    public function test_get_suggestions_stand4_returns_empty()
    {
        Http::fake([
            'www.acronymfinder.com/*' => Http::response('', 404),
        ]);

        Log::shouldReceive('info')->atLeast()->once();

        $result = $this->provider->getSuggestions('API');

        $this->assertEmpty($result);
    }

    public function test_get_suggestions_exception_handling()
    {
        Http::fake([
            'www.acronymfinder.com/*' => function () {
                throw new \Exception('Network error');
            },
        ]);

        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('warning')->atLeast()->once();

        $result = $this->provider->getSuggestions('API');

        $this->assertEmpty($result);
    }

    public function test_get_suggestions_no_valid_meanings()
    {
        $mockHtml = '<html>
            <td class="meaning">API</td>
            <td class="meaning">Test</td>
        </html>';

        Http::fake([
            'www.acronymfinder.com/*' => Http::response($mockHtml, 200),
        ]);

        Log::shouldReceive('info')->atLeast()->once();

        $result = $this->provider->getSuggestions('API');

        $this->assertEmpty($result);
    }

    public function test_parse_acronym_finder_html()
    {
        $html = '
        <html>
            <td class="meaning">Application Programming Interface</td>
            <td class="meaning">Advanced Persistent Instruction</td>
            <td class="meaning">Short</td>
            <td class="meaning">Another Programming Interface</td>
            <td class="meaning">Application Programming Interface</td>
        </html>';

        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('parseAcronymFinderHtml');
        $method->setAccessible(true);

        $result = $method->invoke($this->provider, $html, 'API');

        $this->assertGreaterThan(0, count($result));
        Assert::assertContains('Application Programming Interface', $result);
        Assert::assertContains('Advanced Persistent Instruction', $result);
        Assert::assertNotContains('Short', $result); // Too short

        // Check uniqueness
        $this->assertEquals(3, count($result)); // Should be unique values
    }

    public function test_parse_acronym_finder_html_no_matches()
    {
        $html = '<html><body>No meanings found</body></html>';

        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('parseAcronymFinderHtml');
        $method->setAccessible(true);

        $result = $method->invoke($this->provider, $html, 'API');

        $this->assertEmpty($result);
    }

    public function test_translate_to_hrvatski()
    {
        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('translateToHrvatski');
        $method->setAccessible(true);

        $testCases = [
            'Application Corporation' => 'Application Korporacija',
            'Software Company' => 'Software Tvrtka',
            'Technology Institute' => 'Tehnologija Institut',
            'University System' => 'Sveučilište Sustav',
            'Research Organization' => 'Istraživanje Organizacija',
            'Network Service' => 'Mreža Usluga',
            'Development Department' => 'Razvoj Odjel',
            'Management Association' => 'Upravljanje Udruga',
            'Administration Network' => 'Uprava Mreža',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $method->invoke($this->provider, $input);
            $this->assertEquals($expected, $result, "Failed for: $input");
        }
    }

    public function test_translate_to_hrvatski_case_insensitive()
    {
        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('translateToHrvatski');
        $method->setAccessible(true);

        $result1 = $method->invoke($this->provider, 'CORPORATION');
        $this->assertEquals('Korporacija', $result1);

        $result2 = $method->invoke($this->provider, 'corporation');
        $this->assertEquals('Korporacija', $result2); // str_ireplace preserves case of replacement

        $result3 = $method->invoke($this->provider, 'Corporation');
        $this->assertEquals('Korporacija', $result3);
    }

    public function test_suggest_category_technology()
    {
        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('suggestCategory');
        $method->setAccessible(true);

        $techTerms = [
            'Computer Technology',
            'Software Development',
            'IT Department',
            'Internet Protocol',
            'Web Application',
            'Database System',
            'tech solution',
        ];

        foreach ($techTerms as $term) {
            $result = $method->invoke($this->provider, $term);
            $this->assertEquals('Tehnologija', $result, "Failed for: $term");
        }
    }

    public function test_suggest_category_medicine()
    {
        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('suggestCategory');
        $method->setAccessible(true);

        $medicalTerms = [
            'medic association',
            'health department',
            'hospital network',
            'clinic services',
            'disease control',
            'treatment center',
        ];

        foreach ($medicalTerms as $term) {
            $result = $method->invoke($this->provider, $term);
            $this->assertEquals('Medicina', $result, "Failed for: $term");
        }
    }

    public function test_suggest_category_business()
    {
        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('suggestCategory');
        $method->setAccessible(true);

        $businessTerms = [
            'business Corporation',
            'company Management',
            'market Research',
            'finance Services',
        ];

        foreach ($businessTerms as $term) {
            $result = $method->invoke($this->provider, $term);
            $this->assertEquals('Poslovanje', $result, "Failed for: $term");
        }
    }

    public function test_suggest_category_education()
    {
        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('suggestCategory');
        $method->setAccessible(true);

        $educationTerms = [
            'Education Department',
            'School Administration',
            'University Research',
            'Academic College',
            'Student Services',
        ];

        foreach ($educationTerms as $term) {
            $result = $method->invoke($this->provider, $term);
            $this->assertEquals('Obrazovanje', $result, "Failed for: $term");
        }
    }

    public function test_suggest_category_government()
    {
        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('suggestCategory');
        $method->setAccessible(true);

        $governmentTerms = [
            'Government Agency',
            'Administration Department',
            'Ministry Office',
            'Department of Defense',
        ];

        foreach ($governmentTerms as $term) {
            $result = $method->invoke($this->provider, $term);
            $this->assertEquals('Vlada', $result, "Failed for: $term");
        }
    }

    public function test_suggest_category_default()
    {
        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('suggestCategory');
        $method->setAccessible(true);

        $result = $method->invoke($this->provider, 'Random Unknown Term');
        $this->assertEquals('Općenito', $result);
    }

    public function test_get_from_stand4_always_returns_empty()
    {
        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('getFromStand4');
        $method->setAccessible(true);

        Log::shouldReceive('info')->once();

        $result = $method->invoke($this->provider, 'API');
        $this->assertEmpty($result);
    }

    public function test_get_from_acronym_finder_successful()
    {
        $mockHtml = '<html>
            <td class="meaning">Application Programming Interface</td>
            <td class="meaning">Advanced Programming Instructions</td>
        </html>';

        Http::fake([
            'www.acronymfinder.com/*' => Http::response($mockHtml, 200),
        ]);

        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('getFromAcronymFinder');
        $method->setAccessible(true);

        $result = $method->invoke($this->provider, 'API');

        $this->assertNotEmpty($result);
        $this->assertLessThanOrEqual(3, count($result));
        $this->assertEquals('AcronymFinder', $result[0]['source']);
        $this->assertStringContainsString('Programming', $result[0]['meaning']);
    }

    public function test_get_from_acronym_finder_http_failure()
    {
        Http::fake([
            'www.acronymfinder.com/*' => Http::response('', 404),
        ]);

        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('getFromAcronymFinder');
        $method->setAccessible(true);

        $result = $method->invoke($this->provider, 'API');
        $this->assertEmpty($result);
    }

    public function test_get_from_acronym_finder_exception()
    {
        Http::fake([
            'www.acronymfinder.com/*' => function () {
                throw new \Exception('Connection failed');
            },
        ]);

        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('getFromAcronymFinder');
        $method->setAccessible(true);

        Log::shouldReceive('warning')->once();

        $result = $method->invoke($this->provider, 'API');
        $this->assertEmpty($result);
    }

    public function test_suggestions_limit_to_three()
    {
        $mockHtml = '<html>';
        for ($i = 1; $i <= 10; $i++) {
            $mockHtml .= "<td class=\"meaning\">Very Long Meaning Number $i That Should Be Included</td>";
        }
        $mockHtml .= '</html>';

        Http::fake([
            'www.acronymfinder.com/*' => Http::response($mockHtml, 200),
        ]);

        Log::shouldReceive('info')->atLeast()->once();

        $result = $this->provider->getSuggestions('API');

        $this->assertCount(3, $result); // Should be limited to 3
    }

    public function test_filters_short_meanings()
    {
        $mockHtml = '<html>
            <td class="meaning">Application Programming Interface</td>
            <td class="meaning">API</td>
            <td class="meaning">App</td>
            <td class="meaning">Advanced Programming Interface</td>
        </html>';

        Http::fake([
            'www.acronymfinder.com/*' => Http::response($mockHtml, 200),
        ]);

        Log::shouldReceive('info')->atLeast()->once();

        $result = $this->provider->getSuggestions('API');

        $this->assertCount(2, $result);
        foreach ($result as $suggestion) {
            $this->assertGreaterThan(10, strlen($suggestion['original_meaning']));
        }
    }

    public function test_complex_integration_flow()
    {
        // Test the complete flow: Stand4 fails -> AcronymFinder succeeds
        $mockHtml = '<html>
            <td class="meaning">Application tech Interface</td>
            <td class="meaning">Advanced Programming Instructions</td>
        </html>';

        Http::fake([
            'www.acronymfinder.com/*' => Http::response($mockHtml, 200),
        ]);

        Log::shouldReceive('info')->atLeast()->once();

        $result = $this->provider->getSuggestions('API');

        $this->assertNotEmpty($result);
        $this->assertEquals('AcronymFinder', $result[0]['source']);
        $this->assertEquals('english_meaning', $result[0]['type']);
        $this->assertArrayHasKey('original_meaning', $result[0]);
        $this->assertArrayHasKey('category', $result[0]);
        $this->assertEquals('Tehnologija', $result[0]['category']); // Should detect as tech due to "tech"
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}

<?php
declare(strict_types=1);

namespace AutoRoute;

class CreatorTest extends \PHPUnit\Framework\TestCase
{
    public function test()
    {
        $autoRoute = new AutoRoute(
            namespace: 'AutoRoute\\Http',
            directory: __DIR__ . DIRECTORY_SEPARATOR . 'Http',
            suffix: 'Action',
            method: 'exec',
        );

        $creator = $autoRoute->getCreator();

        $template = file_get_contents(
            dirname(__DIR__) . '/resources/templates/action.tpl'
        );

        [$file, $code] = $creator->create(
            'GET',
            '/company/{companyId}/employee/{employeeNum}',
            $template
        );

        $expect = str_replace(
            '/',
            DIRECTORY_SEPARATOR,
            __DIR__ . DIRECTORY_SEPARATOR . 'Http/Company/Employee/GetCompanyEmployeeAction.php'
        );

        $this->assertSame($expect, $file);

        $expect = '<?php
namespace AutoRoute\Http\Company\Employee;

class GetCompanyEmployeeAction
{
    public function exec($companyId, $employeeNum)
    {
    }
}
';

        $this->assertSame($expect, $code);
    }
}

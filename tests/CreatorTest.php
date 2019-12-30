<?php
declare(strict_types=1);

namespace AutoRoute;

class CreatorTest extends \PHPUnit\Framework\TestCase
{
    public function test()
    {
        $autoRoute = new AutoRoute(
            'AutoRoute\\Http',
            __DIR__ . DIRECTORY_SEPARATOR . 'Http'
        );
        $autoRoute->setSuffix('Action');
        $autoRoute->setMethod('exec');
        $creator = $autoRoute->newCreator(file_get_contents(
            dirname(__DIR__) . '/resources/templates/action.tpl'
        ));

        [$file, $code] = $creator->create(
            'GET',
            '/company/{companyId}/employee/{employeeNum}'
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

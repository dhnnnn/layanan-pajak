<?php

namespace App\Http\Controllers\FieldOfficer;

use App\Actions\FieldOfficer\GetFieldOfficerArrearsAction;
use App\Actions\FieldOfficer\GetFieldOfficerDistrictStatsAction;
use App\Actions\FieldOfficer\GetFieldOfficerMonthlyRealizationAction;
use App\Actions\FieldOfficer\GetFieldOfficerTargetAchievementAction;
use App\Actions\FieldOfficer\GetTaxpayerArrearsDetailAction;
use App\Actions\FieldOfficer\GetTaxpayerDetailAction;
use App\Actions\FieldOfficer\SearchTaxpayersAction;
use App\Actions\Simpadu\BuildTaxPayerFilterAction;
use App\Actions\Simpadu\GetTaxPayerMatrixAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\FieldOfficer\AchievementRequest;
use App\Http\Requests\FieldOfficer\ArrearsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    public function assignedDistricts(Request $request, GetFieldOfficerDistrictStatsAction $action): View
    {
        $year = $request->integer('year', (int) date('Y'));

        return view('field-officer.wp-by-district', $action->execute($request->user(), $year));
    }

    public function targetAchievement(AchievementRequest $request, GetFieldOfficerTargetAchievementAction $action): View
    {
        return view('field-officer.target-achievement', $action->execute($request->user(), $request->validated()));
    }

    public function monthlyRealization(Request $request, GetFieldOfficerMonthlyRealizationAction $action): View
    {
        $year = $request->integer('year', (int) date('Y'));

        return view('field-officer.monthly-realization', $action->execute($request->user(), $year));
    }

    public function arrears(ArrearsRequest $request, GetFieldOfficerArrearsAction $action): View
    {
        return view('field-officer.arrears', $action->execute($request->user(), $request->validated()));
    }

    public function search(Request $request, SearchTaxpayersAction $action): View
    {
        return view('field-officer.search', $action->execute($request->user(), $request->all()));
    }

    public function wpTunggakan(Request $request, GetTaxpayerArrearsDetailAction $action): JsonResponse
    {
        $year = $request->integer('year', (int) date('Y'));
        $npwpd = (string) $request->query('npwpd');
        $nop = (string) $request->query('nop');

        return response()->json($action->execute($npwpd, $nop, $year));
    }

    public function taxpayers(Request $request, BuildTaxPayerFilterAction $buildFilter): Response|View
    {
        $data = $buildFilter->execute($request, app(GetTaxPayerMatrixAction::class));

        if ($request->ajax()) {
            return response(view('admin.monitoring._table', $data)->render());
        }

        return view('field-officer.tax-payers', $data);
    }

    public function taxpayerDetail(Request $request, string $npwpd, GetTaxpayerDetailAction $action): View
    {
        $year = $request->integer('year', (int) date('Y'));
        $data = $action->execute($request->user(), $npwpd, $year);

        if (empty($data)) {
            abort(404, 'Data WP tidak ditemukan');
        }

        return view('field-officer.taxpayer-detail', $data);
    }
}

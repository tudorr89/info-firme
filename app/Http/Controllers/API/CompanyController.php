<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyRequest;
use App\Models\Company;
use Illuminate\Support\Facades\Cache;

class CompanyController extends Controller
{
    public function __invoke(CompanyRequest $request)
    {
        if($request->cui) {
            $company = Cache::remember($request->cui, 60 * 60 * 24, function () use ($request) {
                return Company::with(['address','info','status.details'])->where('cui', $request->cui)->firstorFail();
            });

            return response()->json($company);
        }
        $company = Cache::remember($request->company, 60 * 60 * 24, function () use ($request) {
            return Company::with(['address','info','status.details'])->where('name', 'LIKE', $request->company.'%')->paginate();
        });

        return response()->json($company);
    }
}

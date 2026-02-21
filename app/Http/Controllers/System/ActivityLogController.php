<?php
namespace App\Http\Controllers\System;
use App\Http\Controllers\Controller;

// for controller output
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Response;
use Illuminate\View\View;

// models
use App\Models\ActivityLog;

// load db facade
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

// load validation
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
// use {{ namespacedRequests }}

// load batch and queue
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;

// load email & notification
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;// more email

// load pdf
// use Barryvdh\DomPDF\Facade\Pdf;

// load helper
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

// load Carbon library
use \Carbon\Carbon;
use \Carbon\CarbonPeriod;
use \Carbon\CarbonInterval;

use Session;
use Throwable;
use Exception;
use Log;

class ActivityLogController extends Controller
{
	public function index()
	{
		return view('system.activity_logs.index');
	}

	public function show(ActivityLog $log)
	{
		return view('system.activity_logs.show', compact('log'));
	}

	public function destroy(ActivityLog $log)
	{
		$log->delete();
		return response()->json(['success' => true]);
	}

	public function getActivityLogs(Request $request): JsonResponse
	{
		$columns = [
			0 => 'id',
			1 => 'user',
			2 => 'event',
			3 => 'model_type',
			4 => 'route_name',
			5 => 'method',
			6 => 'url',
			7 => 'ip_address',
			8 => 'user_agent',
			9 => 'is_critical',
			10 => 'created_at',
		];

		$query = ActivityLog::select([
			'id',
			'user',
			'event',
			'model_type',
			'route_name',
			'method',
			'url',
			'ip_address',
			'user_agent',
			'is_critical',
			'created_at',
		]);

		if ($request->search_value) {
			$search = $request->search_value;

			$query->where(function ($q) use ($search) {
				$q->where('model_type', 'LIKE', "%{$search}%")
				->orWhere('ip_address', 'LIKE', "%{$search}%")
				->orWhere('model_id', 'LIKE', "%{$search}%")
				->orWhere('created_at', 'LIKE', "%{$search}%")
				->orWhere('route_name', 'LIKE', "%{$search}%")
				->orWhere('user', 'LIKE', "%{$search}%");
			});
		}

		$totalRecords = ActivityLog::count();
		$filteredRecords = (clone $query)->count();

		$orderColumn = $columns[$request->order[0]['column']] ?? 'created_at';
		$orderDir = $request->order[0]['dir'] ?? 'desc';

		$data = $query
		->orderBy($orderColumn, $orderDir)
		->skip($request->start)
		->take($request->length)
		->get();

		return response()->json([
			'draw' => intval($request->draw),
			'recordsTotal' => $totalRecords,
			'recordsFiltered' => $filteredRecords,
			'data' => $data,
		]);
	}

}

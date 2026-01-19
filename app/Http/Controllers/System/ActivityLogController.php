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

	// this 1 need chunks sooner or later
	// public function getActivityLogs(Request $request): JsonResponse
	// {
	// 	$values = ActivityLog::with('belongstouser')
	// 										->when($request->search, function(Builder $query) use ($request){
	// 											$query->where('model_type','LIKE','%'.$request->search.'%')
	// 											->orWhere('ip_address','LIKE','%'.$request->search.'%');
	// 										})
	// 										->when($request->id, function($query) use ($request){
	// 											$query->where('id', $request->id);
	// 										})
	// 										->orderBy('created_at', 'DESC')
	// 										->get();
	// 	return response()->json($values);
	// }

	public function getActivityLogs(Request $request): JsonResponse
	{
		$columns = [
			0 => 'id',
			1 => 'event',
			2 => 'model_type',
			3 => 'staff_id',
			4 => 'ip_address',
			5 => 'created_at',
		];

		$query = ActivityLog::with('belongstouser');

		if ($request->search_value) {
			$search = $request->search_value;

			$query->where(function ($q) use ($search) {
				$q->where('model_type', 'LIKE', "%{$search}%")
				->orWhere('ip_address', 'LIKE', "%{$search}%")

		  // 👇 search related user name
				->orWhereHas('belongstouser', function ($uq) use ($search) {
					$uq->where('name', 'LIKE', "%{$search}%");
				});
			});
		}

		$totalRecords = ActivityLog::count();
		$filteredRecords = $query->count();

	// 🔃 Ordering
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

@extends('layouts.app')

@section('content')
<div class="card">
	<div class="card-header d-flex justify-content-between align-items-center">
		<h5 class="mb-0"><i class="fa fa-history"></i> Activity Logs</h5>
	</div>
	<div class="card-body">
		<table id="logs-table" class="table table-striped table-bordered w-100"></table>
	</div>
</div>
@endsection

@section('js')
window.data = {
	route: {
		getActivityLogs: '{{ route('getActivityLogs') }}',
	},
	url: {
		activitylogs: '{{ url('activity-logs') }}',
	},
	old: {
	},
	errors: @json($errors->toArray()),
};

@endsection

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
var table = $('#logs-table').DataTable({
	lengthMenu: [ [100, 200, 500, 1000], [100, 200, 500, 1000] ],
	order: [[ 0, 'desc' ], [1, 'desc']],
	responsive: true,
	autoWidth: true,
	fixedHeader: true,
	processing: true,
	serverSide: true,

	// dom: 'Bfrtip',
	ajax: {
		url: '{{ route('getActivityLogs') }}',
		type: 'GET',
		// dataSrc: '',
		data: function (d) {
			d._token = '{!! csrf_token() !!}'
			d.search_value = d.search.value; // map DataTables search
		}
	},
	columns: [
		{ data: 'id', title: 'ID' },
		{
			data: 'event',
			title: 'Event',
			render: data =>
			data.charAt(0).toUpperCase() + data.slice(1) },		// Combine model_type + model_id here 👇
		{
			data: null,
			title: 'Model',
			render: function (data, type, row) {
				// Extract only class name from fully qualified model type
				let modelName = row.model_type ? row.model_type.split('\\').pop() : '-';
				let modelId = row.model_id ? ` #${row.model_id}` : '';
				return `${modelName}${modelId}`;
			}
		},
		// {
		// 	data: null,
		// 	title:'User',
		// 	defaultContent: 'System',
		// 	render: function(data, type, row){
		// 		if(
		// 			data.staff_id == 117 ||
		// 			data.staff_id == 72
		// 		){
		// 			return `Admin`;
		// 		}
		// 		return data.belongstouser.name;
		// 	}
		// },
		{
			data: 'name',
			title:'User',
			defaultContent: 'System'
		},
		{ data:'ip_address', title:'IP Address' },
		{ data:'created_at', title:'Timestamp', render: data => moment(data).format('D MMM YYYY h:mm a') },
		{
			data: 'id',
			title: '#',
			orderable: false,
			searchable:false,
			render: function(id){
				return `
				<div class="btn-group btn-group-sm" role="group">
					<a href="{{ url('activity-logs') }}/${id}" class="btn btn-sm btn-outline-primary">
						<i class="fa-regular fa-eye"></i>
					</a>
					<button type="button" class="btn btn-sm btn btn-outline-danger btn-del" data-id="${id}">
						<i class="fa-regular fa-trash-can"></i>
					</button>
				</div>
				`
			}
		}
	],
	initComplete: function(settings, response) {
		console.log(settings, response); // This runs after successful loading
	}
});

$(document).on('click', '.btn-del', function (e) {
	const id = $(this).data('id');
	swal.fire({
		title: 'Delete Log?',
		text: 'This will delete the log record.',
		icon: 'warning',
		showCancelButton: true,
		confirmButtonText: 'Yes, delete it'
	}).then(res=>{
		if(res.isConfirmed){
			$.ajax({
				url: `{{ url("activity-logs") }}/${id}`,
				type: 'DELETE',
				data: {_token:'{{ csrf_token() }}'},
				success: ()=> table.ajax.reload()
			});
		}
	});
});
@endsection

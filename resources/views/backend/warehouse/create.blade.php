@extends('backend.layout.main')
@section('content')

@push('css')
<style>
/* Base container alignment */
.custom-control.custom-switch {
    display: inline-flex;
    align-items: center;
    cursor: pointer;
}

/* Switch track */
.custom-control-input:checked ~ .custom-control-label::before {
    background-color: #7c5cc4;
    border-color: #7c5cc4;
}

.custom-control-input:not(:checked) ~ .custom-control-label::before {
    background-color: #ddd;
    border-color: #ddd;
}

/* Switch knob */
.custom-control-label::before {
    height: 22px;
    width: 40px;
    border-radius: 20px;
    background-color: #ccc;
    border: 1px solid #aaa;
    transition: background-color 0.25s, border-color 0.25s;
}

.custom-control-label::after {
    top: 5px;
    left: 2px;
    width: 18px;
    height: 18px;
    background-color: #fff;
    border-radius: 50%;
    transition: transform 0.25s ease-in-out;
}

/* Move knob when checked */
.custom-control-input:checked ~ .custom-control-label::after {
    transform: translateX(18px);
}

/* Accessibility focus outline */
.custom-control-input:focus ~ .custom-control-label::before {
    box-shadow: 0 0 0 0.2rem rgba(124, 92, 196, 0.25);
}

/* Optional: hover effect */
.custom-control-label:hover::before {
    filter: brightness(0.95);
}

/* Optional: disable text selection */
.custom-control-label {
    user-select: none;
}
</style>
@endpush

<x-validation-error fieldName="name" />
<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <a href="#" data-toggle="modal" data-target="#createModal" class="btn btn-info add-warehouse-btn"><i class="dripicons-plus"></i> {{__('db.Add Warehouse')}}</a>
        <a href="#" data-toggle="modal" data-target="#importWarehouse" class="btn btn-primary add-warehouse-btn"><i class="dripicons-copy"></i> {{__('db.Import Warehouse')}}</a>
    </div>
    <div class="table-responsive">
        <table id="warehouse-table" class="table">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{__('db.Warehouse')}}</th>
                    <th>{{__('db.Phone Number')}}</th>
                    <th>{{__('db.Email')}}</th>
                    <th>{{__('db.Address')}}</th>
                    <th>{{__('db.Number of Product')}}</th>
                    <th>{{__('db.Stock Quantity')}}</th>
                    <th>{{__('db.status')}}</th>
                    <th class="not-exported">{{__('db.action')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lims_warehouse_all as $key=>$warehouse)
                <?php
                    $number_of_product = App\Models\Product_Warehouse::
                    join('products', 'product_warehouse.product_id', '=', 'products.id')
                    ->where([ ['product_warehouse.warehouse_id', $warehouse->id],
                              ['products.is_active', true]
                    ])->count();

                    $stock_qty = App\Models\Product_Warehouse::
                    join('products', 'product_warehouse.product_id', '=', 'products.id')
                    ->where([ ['product_warehouse.warehouse_id', $warehouse->id],
                              ['products.is_active', true]
                    ])->sum('product_warehouse.qty');
                ?>
                <tr data-id="{{$warehouse->id}}">
                    <td>{{$key}}</td>
                    <td>{{ $warehouse->name }}</td>
                    <td>{{ $warehouse->phone}}</td>
                    <td>{{ $warehouse->email}}</td>
                    <td>{{ $warehouse->address}}</td>
                    <td>{{$number_of_product}}</td>
                    <td>{{$stock_qty}}</td>
                    <td class="text-center">
                        <div class="custom-control custom-switch">
                            <input type="checkbox"
                                class="custom-control-input warehouse-status-toggle"
                                id="switch_{{ $warehouse->id }}"
                                data-id="{{ $warehouse->id }}"
                                {{ $warehouse->is_active ? 'checked' : '' }}>
                            <label class="custom-control-label" for="switch_{{ $warehouse->id }}"></label>
                        </div>
                        <span class="d-none status-label">{{ $warehouse->is_active ? __('db.Active') : __('db.Inactive') }}</span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{__('db.action')}}
                                <span class="caret"></span>
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li>
                                    <button type="button" data-id="{{$warehouse->id}}" class="open-EditWarehouseDialog btn btn-link" data-toggle="modal" data-target="#editModal"><i class="dripicons-document-edit"></i> {{__('db.edit')}}
                                </button>
                                </li>
                                <li class="divider"></li>
                                <form action="{{ route('warehouse.destroy', $warehouse->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <li>
                                        <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="dripicons-trash"></i> {{__('db.delete')}}</button>
                                    </li>
                                </form>
                            </ul>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

<div id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
  <div role="document" class="modal-dialog">
    <div class="modal-content">
        <form action="{{ route('warehouse.store') }}" method="POST">
            @csrf
      <div class="modal-header">
        <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Warehouse')}}</h5>
        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
      </div>
      <div class="modal-body">
        <p class="italic"><small>{{__('db.The field labels marked with * are required input fields')}}.</small></p>
          <div class="form-group">
            <label>{{__('db.name')}} *</label>
            <input type="text" placeholder="{{ __('db.Type WareHouse Name') }}" name="name" required="required" class="form-control">
          </div>
          <div class="form-group">
            <label>{{__('db.Phone Number')}} *</label>
            <input type="text" name="phone" class="form-control" required>
          </div>
          <div class="form-group">
            <label>{{__('db.Email')}}</label>
            <input type="email" name="email" placeholder="example@example.com" class="form-control">
          </div>
          <div class="form-group">
            <label>{{__('db.Address')}} *</label>
            <textarea required class="form-control" rows="3" name="address"></textarea>
          </div>
          <div class="form-group">
            <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
          </div>
      </div>
      </form>
    </div>
  </div>
</div>

<div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
  <div role="document" class="modal-dialog">
    <div class="modal-content">
        <form action="{{ route('warehouse.update', 1) }}" method="POST">
            @csrf
            @method('PUT')
      <div class="modal-header">
        <h5 id="exampleModalLabel" class="modal-title"> {{__('db.Update Warehouse')}}</h5>
        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
      </div>
      <div class="modal-body">
        <p class="italic"><small>{{__('db.The field labels marked with * are required input fields')}}.</small></p>
          <div class="form-group">
            <input type="hidden" name="warehouse_id">
            <label>{{__('db.name')}} *</label>
            <input type="text" placeholder="{{ __('db.Type WareHouse Name') }}" name="name" required="required" class="form-control">
          </div>
          <div class="form-group">
            <label>{{__('db.Phone Number')}} *</label>
            <input type="text" name="phone" class="form-control" required>
          </div>
          <div class="form-group">
            <label>{{__('db.Email')}}</label>
            <input type="email" name="email" placeholder="example@example.com" class="form-control">
          </div>
          <div class="form-group">
            <label>{{__('db.Address')}} *</label>
            <textarea class="form-control" rows="3" name="address" required></textarea>
          </div>
          <div class="form-group">
            <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
          </div>
      </div>
      </form>
    </div>
  </div>
</div>

<div id="importWarehouse" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
  <div role="document" class="modal-dialog">
    <div class="modal-content">
        <form action="{{ route('warehouse.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
      <div class="modal-header">
        <h5 id="exampleModalLabel" class="modal-title">{{__('db.Import Warehouse')}}</h5>
        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
      </div>
      <div class="modal-body">
        <p class="italic"><small>{{__('db.The field labels marked with * are required input fields')}}.</small></p>
         <p>{{__('db.The correct column order is')}} (name*, phone, email, address*) {{__('db.and you must follow this')}}.</p>
        <div class="row">
              <div class="col-md-6">
                  <div class="form-group">
                      <label>{{__('db.Upload CSV File')}} *</label>
                      <input type="file" name="file" class="form-control" required>
                  </div>
              </div>
              <div class="col-md-6">
                  <div class="form-group">
                      <label> {{__('db.Sample File')}}</label>
                      <a href="sample_file/sample_warehouse.csv" class="btn btn-info btn-block btn-md"><i class="dripicons-download"></i>  {{__('db.Download')}}</a>
                  </div>
              </div>
        </div>
        <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
      </div>
      </form>
    </div>
  </div>
</div>


@endsection

@push('scripts')
<script type="text/javascript">

    $("ul#setting").siblings('a').attr('aria-expanded','true');
    $("ul#setting").addClass("show");
    $("ul#setting #warehouse-menu").addClass("active");

    @if(config('database.connections.saleprosaas_landlord'))
        numberOfWarehouse = <?php echo json_encode($numberOfWarehouse)?>;
        $.ajax({
            type: 'GET',
            async: false,
            url: '{{route("package.fetchData", $general_setting->package_id)}}',
            success: function(data) {
                if(data['number_of_warehouse'] > 0 && data['number_of_warehouse'] <= numberOfWarehouse) {
                    $("a.add-warehouse-btn").addClass('d-none');
                }
            }
        });
    @endif

    var user_verified = <?php echo json_encode(env('USER_VERIFIED')) ?>;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

  function confirmDelete() {
      if (confirm("Are you sure want to delete?")) {
          return true;
      }
      return false;
  }

    $(document).ready(function() {

        $(document).on('click', '.open-EditWarehouseDialog', function() {
            var url = "warehouse/"
            var id = $(this).data('id').toString();
            url = url.concat(id).concat("/edit");

            $.get(url, function(data) {
                $("#editModal input[name='name']").val(data['name']);
                $("#editModal input[name='phone']").val(data['phone']);
                $("#editModal input[name='email']").val(data['email']);
                $("#editModal textarea[name='address']").val(data['address']);
                $("#editModal input[name='warehouse_id']").val(data['id']);

            });
        });
  });

  $('#warehouse-table').DataTable( {
        "order": [],
        'language': {
            'lengthMenu': '_MENU_ {{__("db.records per page")}}',
             "info":      '<small>{{__("db.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
            "search":  '{{__("db.Search")}}',
            'paginate': {
                    'previous': '<i class="dripicons-chevron-left"></i>',
                    'next': '<i class="dripicons-chevron-right"></i>'
            }
        },
        'columnDefs': [
            {
                "orderable": false,
                'targets': [0, 5, 6, 7, 8]
            },
            {
                'render': function(data, type, row, meta){
                    if(type === 'display'){
                        data = '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                    }

                   return data;
                },
                'checkboxes': {
                   'selectRow': true,
                   'selectAllRender': '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>'
                },
                'targets': [0]
            }
        ],
        'select': { style: 'multi',  selector: 'td:first-child'},
        'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
        dom: '<"row"lfB>rtip',
        buttons: [
            {
                extend: 'pdf',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
            },
            {
                extend: 'excel',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
            },
            {
                extend: 'csv',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
            },
            {
                extend: 'print',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
            },
            {
                text: '<i title="delete" class="dripicons-cross"></i>',
                className: 'buttons-delete',
                action: function ( e, dt, node, config ) {
                    if(user_verified == '1') {
                        var warehouse_id = [];
                        let selectedRows = [];
                        $('#warehouse-table tbody input.dt-checkboxes:checked').each(function(i) {
                            let row = $(this).closest('tr');
                            warehouse_id.push(row.data('id'));
                            selectedRows.push(row);
                        });

                        if(warehouse_id.length && confirm("Are you sure want to delete?")) {
                            $.ajax({
                                type:'POST',
                                url:'warehouse/deletebyselection',
                                data:{
                                    warehouseIdArray: warehouse_id
                                },
                                success:function(data){
                                    selectedRows.forEach(function(row){
                                        dt.row(row).remove();
                                    });
                                    dt.draw(false);
                                    alert(data);
                                }
                            });

                            // dt.rows({ page: 'current', selected: true }).remove().draw(false);
                        }
                        else if(!warehouse_id.length)
                            alert('No warehouse is selected!');
                    }
                    else
                        alert('This feature is disable for demo!');
                }
            },
            {
                extend: 'colvis',
                text: '<i title="" class="fa fa-eye"></i>',
                columns: ':gt(0)'
            },
        ],
    } );

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

$( "#select_all" ).on( "change", function() {
    if ($(this).is(':checked')) {
        $("#warehouse-table tbody input.dt-checkboxes").prop('checked', true);
    }
    else {
        $("#warehouse-table tbody input.dt-checkboxes").prop('checked', false);
    }
});

$("#export").on("click", function(e){
    e.preventDefault();
    var warehouse = [];
    $('#warehouse-table tbody input.dt-checkboxes:checked').each(function(i){
      warehouse[i] = $(this).closest('tr').data('id') || $(this).val();
    });
    $.ajax({
       type:'POST',
       url:'/exportwarehouse',
       data:{
            warehouseArray: warehouse
        },
       success:function(data){
        alert('Exported to CSV file successfully! Click Ok to download file');
        window.location.href = data;
       }
    });
});

$(document).on('change', '.warehouse-status-toggle', function() {
    let checkbox = $(this);
    let warehouseId = checkbox.data('id');
    let isActive = checkbox.is(':checked') ? 1 : 0;

    if (user_verified == '0') {
        alert('This feature is disable for demo!');
        checkbox.prop('checked', !isActive);
        return;
    }

    $.ajax({
        url: "{{ route('warehouse.toggleStatus') }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            id: warehouseId,
            is_active: isActive
        },
        success: function(response) {
            $('#content .alert.alert-dismissible').remove();
            if (response.success) {
                checkbox.closest('td').find('.status-label').text(isActive ? '{{__("db.Active")}}' : '{{__("db.Inactive")}}');
                var success = '<div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' + response.message + '</div>';
                $('#content').prepend(success);
            } else {
                checkbox.prop('checked', !isActive);
                var error = '<div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' + (response.message || 'Something went wrong') + '</div>';
                $('#content').prepend(error);
            }
        },
        error: function(xhr) {
            checkbox.prop('checked', !isActive);
            $('#content .alert.alert-dismissible').remove();
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Something went wrong';
            var error = '<div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' + msg + '</div>';
            $('#content').prepend(error);
        }
    });
});
</script>
@endpush


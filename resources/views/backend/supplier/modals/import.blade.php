<div id="importSupplier" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('supplier.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 id="exampleModalLabel" class="modal-title">{{__('db.Import Supplier')}}</h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
                </div>
                <div class="modal-body">
                    <p class="italic"><small>{{__('db.The field labels marked with * are required input fields')}}.</small></p>
                    <p>{{__('db.The correct column order is')}} (name*, image, company_name*, vat_number, email*, phone_number*, address*, city*,state, postal_code, country) {{__('db.and you must follow this')}}.</p>
                    <p>{{__('db.To display Image it must be stored in')}} images/supplier {{__('db.directory')}}</p>
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
                                <a href="sample_file/sample_supplier.csv" class="btn btn-info btn-block btn-md"><i class="dripicons-download"></i> {{__('db.Download')}}</a>
                            </div>
                        </div>
                    </div>
                    <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary" id="submit-button">
                </div>
            </form>
        </div>
    </div>
</div>
@extends('admin.layouts.dashboard')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-sm-12">
            <div class="card">
                <form method="POST" id="formPost" action="{{ route('reports.store_ledger') }}">
                    @csrf

                <div class="card-header text-center h4">
                        Libro Mayor
                </div>

                <div class="card-body">
                        <div class="form-group row">
                            <label for="date_end" class="col-sm-1 col-form-label text-md-right">Desde</label>

                            <div class="col-sm-3">
                                <input id="date_begin" type="date" class="form-control @error('date_begin') is-invalid @enderror" name="date_begin" value="{{  date('Y-m-d', strtotime($datebeginyear ?? $date_begin ?? $datenow)) }}" required autocomplete="date_begin">

                                @error('date_begin')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            <label for="date_end" class="col-sm-1 col-form-label text-md-right">hasta </label>

                            <div class="col-sm-3">
                                <input id="date_end" type="date" class="form-control @error('date_end') is-invalid @enderror" name="date_end" value="{{ date('Y-m-d', strtotime($date_end ?? $datenow))}}" required autocomplete="date_end">

                                @error('date_end')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            
                            <div class="col-sm-1">
                            <button type="submit" class="btn btn-primary ">
                                Buscar
                             </button>
                            </div>
                            <div class="col-sm-2  dropdown mb-4">
                                <button class="btn btn-success" type="button"
                                    id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="false"
                                    aria-expanded="false">
                                    <i class="fas fa-bars"></i>
                                    Exportaciones
                                </button>
                                <div class="dropdown-menu animated--fade-in"
                                    aria-labelledby="dropdownMenuButton">
                                    <a href="#" onclick="exportToExcel();" class="dropdown-item bg-light">Exportar a Excel</a> 
                                </div>
                            </div> 
                        </div>
                    </form>
                        <div class="embed-responsive embed-responsive-16by9">
                            <iframe class="embed-responsive-item" src="{{ route('reports.ledger_pdf',[$date_begin ?? $datebeginyear ?? $datenow,$date_end ?? $datenow]) }}" allowfullscreen></iframe>
                          </div>
                        
                        </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('javascript')
    <script>
    
    function exportToExcel(){
        var old_action = document.getElementById("formPost").action;
        document.getElementById("formPost").action = "{{ route('export_reports.ledger') }}";
        document.getElementById("formPost").submit();
        document.getElementById("formPost").action = old_action;
    }
    </script> 
@endsection
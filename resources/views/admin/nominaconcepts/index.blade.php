@extends('admin.layouts.dashboard')

@section('content')

    <ul class="nav nav-tabs justify-content-center" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
        <a class="nav-link  font-weight-bold" style="color: black;" id="home-tab"  href="{{ route('nominas') }}" role="tab" aria-controls="home" aria-selected="true">Nóminas</a>
        </li>
        <li class="nav-item" role="presentation">
        <a class="nav-link active font-weight-bold" style="color: black;" id="profile-tab"  href="{{ route('nominaconcepts') }}" role="tab" aria-controls="profile" aria-selected="false">Conceptos de Nómina</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link font-weight-bold" style="color: black;" id="profile-tab"  href="{{ route('nominabasescalc') }}" role="tab" aria-controls="profile" aria-selected="false">Bases de Cálculo</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link font-weight-bold" style="color: black;" id="profile-tab"  href="{{ route('employees') }}" role="tab" aria-controls="profile" aria-selected="false">Empleados</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link font-weight-bold" style="color: black;" id="profile-tab"  href="{{ route('nominaparts','prestaciones') }}" role="tab" aria-controls="profile" aria-selected="false">Prestaciones</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link font-weight-bold" style="color: black;" id="profile-tab"  href="{{ route('nominaparts','utilidades') }}" role="tab" aria-controls="profile" aria-selected="false">Utilidades</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link font-weight-bold" style="color: black;" id="profile-tab"  href="{{ route('nominaparts','vacaciones') }}" role="tab" aria-controls="profile" aria-selected="false">Vacaciones</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link font-weight-bold" style="color: black;" id="profile-tab"  href="{{ route('nominaparts','liquidaciones') }}" role="tab" aria-controls="profile" aria-selected="false">Liquidaciones</a>
        </li>
    </ul>

<!-- container-fluid -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row py-lg-2">
        <div class="col-md-4">
            <h2>Conceptos de Nóminas</h2>
        </div>
        <div class="col-md-4">
            <a href="{{ route('nominaformulas')}}" class="btn btn-primary float-md-right" role="button" aria-pressed="true">Crear Formula</a>
         
        </div>   
        <div class="col-md-4">
            <a href="{{ route('nominaconcepts.create')}}" class="btn btn-primary float-md-right" role="button" aria-pressed="true">Crear Concepto de Nómina</a>
         
        </div>
        
    </div>

  </div>

  {{-- VALIDACIONES-RESPUESTA--}}
@include('admin.layouts.success')   {{-- SAVE --}}
@include('admin.layouts.danger')    {{-- EDITAR --}}
@include('admin.layouts.delete')    {{-- DELELTE --}}
{{-- VALIDACIONES-RESPUESTA --}}

<div class="card shadow mb-4">
   
   
    <div class="card-body">
        <div class="table-responsive">
        <table class="table table-light2 table-bordered" id="dataTable" width="100%" cellspacing="0">
            <thead>
            <tr>
                <th class="text-center" style="width: 1%;">ID</th>
                <th class="text-center" style="width: 1%;">Concepto</th>
                <th class="text-center" style="width: 20%;">Descripción</th>
                <th class="text-center" style="width: 1%;">Signo-Tipo</th>
                <th class="text-center">Fórmula<br>Mensual - Quincenal - Semanal - Especial - Asignación General</th>
                <th class="text-center" style="width: 1%;">Calcular con Nómina</th>
                <th class="text-center" style="width: 5%;"></th>
              
            </tr>
            </thead>
            
            <tbody>
                @if (empty($nominaconcepts))
                @else
                    @foreach ($nominaconcepts as $nominaconcept)
                    <tr>
                    <td class="text-center font-weight-bold">{{$nominaconcept->id}}<br>O:{{$nominaconcept->order}}</td>
                    <td class="text-center font-weight-bold">{{$nominaconcept->abbreviation}}</td>
                    <td class="text-center">{{$nominaconcept->description}}</td>
                    @if($nominaconcept->sign == "A")
                        <td class="text-center">(Asignación)<br>{{$nominaconcept->type}}</td>
                    @else
                        <td class="text-center">(Deducción)<br>{{$nominaconcept->type}}</td>
                    @endif
                    
                    
                    <td class="text-left">
                        @if (isset($nominaconcept->formulasq['description']))
                        <b>Q</b> = {{$nominaconcept->formulasq['description'] ?? ''}}<br>
                        @endif
                        @if (isset($nominaconcept->formulasm['description']))
                        <b>M</b> = {{$nominaconcept->formulasm['description'] ?? ''}}<br>
                        @endif
                        @if (isset($nominaconcept->formulass['description']))
                        <b>S</b> = {{$nominaconcept->formulass['description'] ?? ''}}<br>
                        @endif
                        @if (isset($nominaconcept->formulase['description']))
                        <b>E</b> = {{$nominaconcept->formulase['description'] ?? ''}}<br>
                        @endif
                        @if (isset($nominaconcept->formulasa['description']))
                        <b>A</b> = {{$nominaconcept->formulasa['description'] ?? ''}}<br>
                        @endif
                        @if (isset($nominaconcept->account_name))
                        <span style="font-size: 10pt;"><b>Cuenta</b> = {{$nominaconcept->account_code}} {{$nominaconcept->account_name}}</span>
                        @endif
                    </td>

                    <!--<td class="text-center">{{ ''/*$nominaconcept->formulasm['description'] ?? ''*/}}</td>-->
                    <!--<td class="text-center">{{ ''/*$nominaconcept->formulass['description'] ?? ''*/}}</td>-->
                    <!--<td class="text-center">{{ ''/*$nominaconcept->formulasq['description'] ?? ''*/}}</td>--> 
                   
                    @if($nominaconcept->calculate == "S")
                        <td class="text-center">Si</td>
                    @else
                        <td class="text-center">No</td>
                    @endif

                    <td class="text-center">
                        <a href="{{route('nominaconcepts.edit',$nominaconcept->id) }}" title="Editar"><i class="fa fa-edit"></i></a>  
                        @if ($nominaconcept->prestations == 'S')
                        <i class="fa fa-address-card" title="Este concepto afecta las Prestaciones" style="color: #4e73df"></i>
                        @endif
                        @if ($nominaconcept->asignation == 'S')
                        <i class="fa fa-address-card" title="Este concepto calculará Asignación" style="color: darkgreen"></i>
                        @endif
                    </td>

                    </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
        </div>
    </div>
</div>

@endsection
@section('javascript')
    <script>
    $('#dataTable').DataTable({
        "ordering": true,
        "order": [1,'desc'],
        'aLengthMenu': [[50, 100, 150, -1], [50, 100, 150, "All"]]
    });
    </script> 
@endsection
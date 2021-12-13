@extends('admin.layouts.dashboard')

@section('content')

<ul class="nav nav-tabs justify-content-center" id="myTab" role="tablist">
    <li class="nav-item" role="presentation">
      <a class="nav-link active font-weight-bold" style="color: black;" id="home-tab"  href="{{ route('products') }}" role="tab" aria-controls="home" aria-selected="true">Productos</a>
    </li>
    <li class="nav-item" role="presentation">
      <a class="nav-link font-weight-bold" style="color: black;" id="profile-tab"  href="{{ route('inventories') }}" role="tab" aria-controls="profile" aria-selected="false">Inventarios</a>
    </li>
    <li class="nav-item" role="presentation">
      <a class="nav-link font-weight-bold" style="color: black;" id="home-tab"  href="{{ route('combos') }}" role="tab" aria-controls="home" aria-selected="true">Combos</a>
  </li>
    <li class="nav-item" role="presentation">
      <a class="nav-link font-weight-bold" style="color: black;" id="contact-tab"  href="{{ route('inventories.movement') }}" role="tab" aria-controls="contact" aria-selected="false">Movimientos de Inventario</a>
    </li>
    
  </ul>

<!-- container-fluid -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row py-lg-2">
      <div class="col-sm-3 offset-sm-4  dropdown mb-4">
          <button class="btn btn-dark" type="button"
              id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="false"
              aria-expanded="false">
              <i class="fas fa-bars"></i>
              Opciones
          </button>
          <div class="dropdown-menu animated--fade-in" aria-labelledby="dropdownMenuButton">
              
              <a href="{{ route('export.product_template') }}" class="dropdown-item bg-success text-white h5">Descargar Plantilla</a> 
              <form id="fileForm" method="POST" action="{{ route('import_product') }}" enctype="multipart/form-data" >
                @csrf
                  <input id="file" type="file" value="import" accept=".xlsx" name="file" class="file">
              </form>
          </div> 
      </div> 
      <div class="col-sm-3">
        <a href="{{ route('products.create')}}" class="btn btn-primary float-md-right" role="button" aria-pressed="true">Registrar un Producto</a>
      </div>
    </div>
  </div>
  <!-- /.container-fluid -->
  {{-- VALIDACIONES-RESPUESTA--}}
  @include('admin.layouts.success')   {{-- SAVE --}}
  @include('admin.layouts.danger')    {{-- EDITAR --}}
  @include('admin.layouts.delete')    {{-- DELELTE --}}
  {{-- VALIDACIONES-RESPUESTA --}}
<!-- DataTales Example -->
<div class="card shadow mb-4">
   
    <div class="card-body">
        <div class="container">
            @if (session('flash'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{session('flash')}}
                <button type="button" class="close" data-dismiss="alert" aria-label="close">
                    <span aria-hidden="true">&times; </span>
                </button>
            </div>   
        @endif
        </div>
        <div class="table-responsive">
        <table class="table table-light2 table-bordered" id="dataTable" width="100%" cellspacing="0">
            <thead>
            <tr> 
                <th class="text-center">Código</th>
                <th class="text-center">Descripción</th>
                <th class="text-center">Tipo</th>
                <th class="text-center">Precio</th>
                <th class="text-center">Moneda</th>
                <th class="text-center">Foto del Producto</th>
              
                <th class="text-center" width="7%"></th>
            </tr>
            </thead>
            
            <tbody>
                @if (empty($products))
                @else  
                    @foreach ($products as $product)
                        <tr>
                           
                            <td class="text-center">{{$product->code_comercial}}</td>
                            <td class="text-center">{{$product->description}}</td>
                            <td class="text-center">{{$product->type}}</td>
                            <td class="text-right">{{number_format($product->price, 2, ',', '.')}}</td>
                            
                            @if ($product->money == 'Bs')
                              <td class="text-center">Bolivares</td>
                            @else
                              <td class="text-center">Dolares</td>
                            @endif
                            
                            <td class="text-center"><img src="{{ asset('/storage/descarga.jpg') }} " ></td>

                            <td class="text-center">
                                <a href="{{ route('products.edit',$product->id) }}"  title="Editar"><i class="fa fa-edit"></i></a>
                                <a href="#" class="delete" data-id-product={{$product->id}} data-toggle="modal" data-target="#deleteModal" title="Eliminar"><i class="fa fa-trash text-danger"></i></a>  
                            </td>
                        </tr>     
                    @endforeach   
                @endif
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- Delete Warning Modal -->
<div class="modal modal-danger fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="Delete" aria-hidden="true">
  <div class="modal-dialog" role="document">
      <div class="modal-content">
          <div class="modal-header">
              <h5 class="modal-title" id="exampleModalLabel">Eliminar</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
              </button>
          </div>
          <div class="modal-body">
          <form action="{{ route('products.delete') }}" method="post">
              @csrf
              @method('DELETE')
              <input id="id_product_modal" type="hidden" class="form-control @error('id_product_modal') is-invalid @enderror" name="id_product_modal" readonly required autocomplete="id_product_modal">
                     
              <h5 class="text-center">Seguro que desea eliminar?</h5>
              
          </div>
          <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-danger">Eliminar</button>
          </div>
          </form>
      </div>
  </div>
</div>
  
@endsection
@section('javascript')
     <script>
        $('#dataTable').DataTable({
            "ordering": false,
            "order": [],
            'aLengthMenu': [[50, 100, 150, -1], [50, 100, 150, "All"]]
        });

        $(document).on('click','.delete',function(){
         
            let id_product = $(this).attr('data-id-product');
    
            $('#id_product_modal').val(id_product);
        });

        $("#file").on('change',function(){
            
            var file = document.getElementById("file").value;

            /*Extrae la extencion del archivo*/
            var basename = file.split(/[\\/]/).pop(),  // extract file name from full path ...
                                               // (supports `\\` and `/` separators)
            pos = basename.lastIndexOf(".");       // get last position of `.`

            if (basename === "" || pos < 1) {
                alert("El archivo no tiene extension");
            }          
            /*-------------------------------*/     

            if(basename.slice(pos + 1) == 'xlsx'){
                document.getElementById("fileForm").submit();
            }else{
                alert("Solo puede cargar archivos .xlsx");
            }            
               
        });
        </script> 
@endsection

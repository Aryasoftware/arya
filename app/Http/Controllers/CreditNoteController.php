<?php

namespace App\Http\Controllers;

use App\Client;
use App\Company;
use App\DetailVoucher;
use App\Http\Controllers\Historial\HistorialcreditnoteController;
use App\Http\Controllers\UserAccess\UserAccessController;
use App\Inventory;
use App\Multipayment;
use App\CreditNote;
use App\CreditCoteDetail;
use App\Transport;
use App\Vendor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CreditNoteController extends Controller
{
    public $userAccess;
    public $modulo = 'Cotizacion';

 
    public function __construct(){

       $this->middleware('auth');
       $this->userAccess = new UserAccessController();
      
   }

   public function index()
   {
        
        if($this->userAccess->validate_user_access($this->modulo)){
            $creditnotes = CreditNote::on(Auth::user()->database_name)->orderBy('id' ,'DESC')
            ->get();

            return view('admin.credit_notes.index',compact('creditnotes'));
        }else{
            return redirect('/home')->withDanger('No tiene Acceso al modulo de '.$this->modulo);
        }

      
   }

   /**
    * Show the form for creating a new resource.
    *
    * @return \Illuminate\Http\Response
    */
    
    public function createcreditnote()
    {
        $transports     = Transport::on(Auth::user()->database_name)->get();

        $date = Carbon::now();
        $datenow = $date->format('Y-m-d');    

        return view('admin.credit_notes.createcreditnote',compact('datenow','transports'));
    }

    public function createcreditnoteclient($id_client)
    {
        $client = null;
                
        if(isset($id_client)){
            $client = Client::on(Auth::user()->database_name)->find($id_client);
        }
        if(isset($client)){

        /* $vendors     = Vendor::on(Auth::user()->database_name)->get();*/

            $transports     = Transport::on(Auth::user()->database_name)->get();

            $date = Carbon::now();
            $datenow = $date->format('Y-m-d');    

            return view('admin.credit_notes.createcreditnote',compact('client','datenow','transports'));

        }else{
            return redirect('/creditnotes')->withDanger('El Cliente no existe');
        } 
    }

    public function createcreditnotevendor($id_client,$id_vendor)
    {
        $client = null;
                
        if(isset($id_client)){
            $client = Client::on(Auth::user()->database_name)->find($id_client);
        }
        if(isset($client)){

            $vendor = null;
                
            if(isset($id_vendor)){
                $vendor = Vendor::on(Auth::user()->database_name)->find($id_vendor);
            }
            if(isset($vendor)){

                /* $vendors     = Vendor::on(Auth::user()->database_name)->get();*/

                $transports     = Transport::on(Auth::user()->database_name)->get();

                $date = Carbon::now();
                $datenow = $date->format('Y-m-d');    

                return view('admin.credit_notes.createcreditnote',compact('client','vendor','datenow','transports'));

            }else{
                return redirect('/creditnotes')->withDanger('El Vendedor no existe');
            } 

        }else{
            return redirect('/creditnotes')->withDanger('El Cliente no existe');
        } 
    }



    public function create($id_creditnote,$coin)
    {
        
        if($this->userAccess->validate_user_access($this->modulo)){
            $creditnote = null;
                
            if(isset($id_creditnote)){
                $creditnote = CreditNote::on(Auth::user()->database_name)->find($id_creditnote);
            }

            if(isset($creditnote) && ($creditnote->status == 1)){
                //$inventories_creditnotes = creditnoteProduct::on(Auth::user()->database_name)->where('id_creditnote',$creditnote->id)->get();
                $inventories_creditnotes = DB::connection(Auth::user()->database_name)->table('products')
                                ->join('inventories', 'products.id', '=', 'inventories.product_id')
                                ->join('creditnote_products', 'inventories.id', '=', 'creditnote_products.id_inventory')
                                ->where('creditnote_products.id_creditnote',$id_creditnote)
                                ->whereIn('creditnote_products.status',['1','C'])
                                ->select('products.*','creditnote_products.price as price','creditnote_products.rate as rate','creditnote_products.id as creditnote_products_id','inventories.code as code','creditnote_products.discount as discount',
                                'creditnote_products.amount as amount_creditnote','creditnote_products.retiene_iva as retiene_iva')
                                ->get(); 
            
                
                $date = Carbon::now();
                $datenow = $date->format('Y-m-d');  

                $company = Company::on(Auth::user()->database_name)->find(1);

                //Si la taza es automatica
                if($company->tiporate_id == 1){
                    //esto es para que siempre se pueda guardar la tasa en la base de datos
                    $bcv_creditnote_product = $this->search_bcv();
                    $bcv = $this->search_bcv();
                }else{
                    //si la tasa es fija
                    $bcv_creditnote_product = $company->rate;
                    $bcv = $company->rate;

                }
               
                if(($coin == 'bolivares') ){
                    
                    $coin = 'bolivares';
                }else{
                    //$bcv = null;

                    $coin = 'dolares';
                }
                
        
                return view('admin.credit_notes.create',compact('creditnote','inventories_creditnotes','datenow','bcv','coin','bcv_creditnote_product'));
            }else{
                return redirect('/creditnotes')->withDanger('No es posible ver esta cotizacion');
            } 
            
        }else{
            return redirect('/home')->withDanger('No tiene Acceso al modulo de '.$this->modulo);
        }

    }

    
    public function search_bcv()
    {
        /*Buscar el indice bcv*/
        $urlToGet ='http://www.bcv.org.ve/tasas-informativas-sistema-bancario';
        $pageDocument = @file_get_contents($urlToGet);
        preg_match_all('|<div class="col-sm-6 col-xs-6 centrado"><strong> (.*?) </strong> </div>|s', $pageDocument, $cap);

        if ($cap[0] == array()){ // VALIDAR Concidencia
            $titulo = '0,00';
        }else {
            $titulo = $cap[1][4];
        }

        $bcv_con_formato = $titulo;
        $bcv = str_replace(',', '.', str_replace('.', '',$bcv_con_formato));


        /*-------------------------- */
        return $bcv;

    }


    public function createproduct($id_creditnote,$coin,$id_inventory)
    {
        $creditnote = null;
                
        if(isset($id_creditnote)){
            $creditnote = CreditNote::on(Auth::user()->database_name)->find($id_creditnote);
        }

        if(isset($creditnote) && ($creditnote->status == 1)){
            //$product_creditnotes = creditnoteProduct::on(Auth::user()->database_name)->where('id_creditnote',$creditnote->id)->get();
                $product = null;
                $inventories_creditnotes = DB::connection(Auth::user()->database_name)->table('products')
                                ->join('inventories', 'products.id', '=', 'inventories.product_id')
                                ->join('creditnote_products', 'inventories.id', '=', 'creditnote_products.id_inventory')
                                ->where('creditnote_products.id_creditnote',$id_creditnote)
                                ->whereIn('creditnote_products.status',['1','C'])
                                ->select('products.*','creditnote_products.price as price','creditnote_products.rate as rate','creditnote_products.id as creditnote_products_id','inventories.code as code','creditnote_products.discount as discount',
                                'creditnote_products.amount as amount_creditnote','creditnote_products.retiene_iva as retiene_iva')
                                ->get(); 
                
                if(isset($id_inventory)){
                    $inventory = Inventory::on(Auth::user()->database_name)->find($id_inventory);
                }
                if(isset($inventory)){

                    $date = Carbon::now();
                    $datenow = $date->format('Y-m-d');    
                    
                    /*Revisa si la tasa de la empresa es automatica o fija*/
                    $company = Company::on(Auth::user()->database_name)->find(1);
                    //Si la taza es automatica
                    if($company->tiporate_id == 1){
                        $bcv_creditnote_product = $this->search_bcv();
                    }else{
                        //si la tasa es fija
                        $bcv_creditnote_product = $company->rate;
                    }


                    if(($coin == 'bolivares')){
                        
                        if($company->tiporate_id == 1){
                            $bcv = $this->search_bcv();
                        }else{
                            //si la tasa es fija
                            $bcv = $company->rate;
                        }
                    }else{
                        //Cuando mi producto esta en Bolivares, pero estoy cotizando en dolares, convierto los bs a dolares
                        if($inventory->products['money'] == 'Bs'){
                            $inventory->products['price'] = $inventory->products['price'] / $creditnote->bcv;
                        }
                        $bcv = null;
                    }
                    $llego ='llego';

                    return view('admin.credit_notes.create',compact('llego','bcv_creditnote_product','creditnote','inventories_creditnotes','inventory','bcv','datenow','coin'));

                }else{
                    return redirect('/creditnotes')->withDanger('El Producto no existe');
                } 
        }else{
            return redirect('/creditnotes')->withDanger('La cotizacion no existe');
        } 

    }

    public function selectproduct($id_creditnote,$coin,$type)
    {

        $services = null;

        $inventories = DB::connection(Auth::user()->database_name)->table('inventories')
            ->join('products', 'products.id', '=', 'inventories.product_id')
            ->where(function ($query){
                $query->where('products.type','MERCANCIA')
                    ->orWhere('products.type','COMBO');
            })
            ->where('products.status',1)
            ->select('products.*','inventories.amount as amount','inventories.id as id_inventory')
            ->orderBy('products.code_comercial','desc')
            ->get();
        
        $creditnote = CreditNote::on(Auth::user()->database_name)->find($id_creditnote);

        $bcv_creditnote_product = $creditnote->bcv;
        
        $company = Company::on(Auth::user()->database_name)->find(1);
        //Si la taza es automatica
        if($company->tiporate_id == 1){
            $bcv = $this->search_bcv();
        }else{
            //si la tasa es fija
            $bcv = $company->rate;
        }

        if(($type == 'servicios') || $inventories->isEmpty()){

            $type = 'servicios';
            $services = DB::connection(Auth::user()->database_name)->table('inventories')
            ->join('products', 'products.id', '=', 'inventories.product_id')
            ->where('products.type','SERVICIO')
            ->where('products.status',1)
            ->select('products.*','inventories.id as id_inventory')
            ->orderBy('products.code_comercial','desc')
            ->get();
            
            return view('admin.credit_notes.selectservice',compact('type','services','id_creditnote','coin','bcv','bcv_creditnote_product'));
        }
    
        return view('admin.credit_notes.selectinventary',compact('type','inventories','id_creditnote','coin','bcv','bcv_creditnote_product'));
    }


    public function createvendor($id_product,$id_vendor)
    {

            $vendor = null;
            
            if(isset($id_vendor)){
                $vendor = vendor::on(Auth::user()->database_name)->find($id_vendor);
            }

            $clients     = Client::on(Auth::user()->database_name)->get();
        
            $vendors     = Vendor::on(Auth::user()->database_name)->get();

            $transports     = Transport::on(Auth::user()->database_name)->get();

            $date = Carbon::now();
            $datenow = $date->format('Y-m-d');    

            return view('admin.credit_notes.create',compact('clients','vendors','datenow','transports','vendor'));
    }

    public function selectvendor($id_client)
    {
            if($id_client != -1){

                $vendors     = vendor::on(Auth::user()->database_name)->get();

                
        
                return view('admin.credit_notes.selectvendor',compact('vendors','id_client'));

            }else{
                return redirect('/creditnotes/registercreditnote')->withDanger('Seleccione un Cliente primero');
            }

        
    }

    public function selectclient()
    {
        $clients     = Client::on(Auth::user()->database_name)->orderBy('name','asc')->get();
    
        return view('admin.credit_notes.selectclient',compact('clients'));
    }
    

    public function store(Request $request)
    {
    
        $data = request()->validate([
            
        
            'id_client'         =>'required',
            'id_transport'         =>'required',
            'id_user'         =>'required',
            'date_creditnote'         =>'required',
        
        ]);

        $id_client = request('id_client');
        $id_vendor = request('id_vendor');

        
        if($id_client != '-1'){
            
                $var = new creditnote();
                $var->setConnection(Auth::user()->database_name);

                $var->id_client = $id_client;
                $var->id_vendor = $id_vendor;

                $id_transport = request('id_transport');
                if($id_transport != '-1'){
                    $var->id_transport = request('id_transport');
                }
                
                $var->id_user = request('id_user');
                $var->serie = request('serie');
                $var->date_creditnote = request('date_creditnote');
        
                $var->observation = request('observation');
                $var->note = request('note');

                $company = Company::on(Auth::user()->database_name)->find(1);
                //Si la taza es automatica
                if($company->tiporate_id == 1){
                    $bcv = $this->search_bcv();
                }else{
                    //si la tasa es fija
                    $bcv = $company->rate;
                }

                $var->bcv = $bcv;

                $var->coin = 'bolivares';
        
                $var->status =  1;
            
                $var->save();


                $historial_creditnote = new HistorialcreditnoteController();

                $historial_creditnote->registerAction($var,"creditnote","Creó Cotización");


                return redirect('creditnotes/register/'.$var->id.'/bolivares');

            
        }else{
            return redirect('/creditnotes/registercreditnote')->withDanger('Debe Buscar un Cliente');
        } 

        
    }


    public function storeproduct(Request $request)
    {
        
        $data = request()->validate([
            
        
            'id_creditnote'         =>'required',
            'id_inventory'         =>'required',
            'amount'         =>'required',
            'discount'         =>'required',
        
        
        ]);

        
        $var = new creditnoteProduct();
        $var->setConnection(Auth::user()->database_name);

        $var->id_creditnote = request('id_creditnote');
        
        $var->id_inventory = request('id_inventory');

        $islr = request('islr');
        if($islr == null){
            $var->retiene_islr = false;
        }else{
            $var->retiene_islr = true;
        }

        $exento = request('exento');
        if($exento == null){
            $var->retiene_iva = false;
        }else{
            $var->retiene_iva = true;
        }

        $coin = request('coin');

        $creditnote = CreditNote::on(Auth::user()->database_name)->find($var->id_creditnote);

        $var->rate = $creditnote->bcv;

        if($var->id_inventory == -1){
            return redirect('creditnotes/register/'.$var->id_creditnote.'')->withDanger('No se encontro el producto!');
            /*echo '<script type="text/javascript">alert("no encontrado sin cantidad");</script>';
            return;*/
        }

        $amount = request('amount');
        $cost = str_replace(',', '.', str_replace('.', '',request('cost')));

        $global = new GlobalController();
        
        $value_return = $global->check_amount($creditnote->id,$var->id_inventory,$amount);

        if($value_return != 'exito'){
                return redirect('creditnotes/registerproduct/'.$var->id_creditnote.'/'.$coin.'/'.$var->id_inventory.'')->withDanger('La cantidad de este producto excede a la cantidad puesta en inventario!');
        }

        

        if($coin == 'dolares'){
            $cost_sin_formato = ($cost) * $var->rate;
        }else{
            $cost_sin_formato = $cost;
        }

        $var->price = $cost_sin_formato;
        

        $var->amount = $amount;

        $var->discount = request('discount');

        if(($var->discount < 0) || ($var->discount > 100)){
            return redirect('creditnotes/register/'.$var->id_creditnote.'/'.$coin.'/'.$var->id_inventory.'')->withDanger('El descuento debe estar entre 0% y 100%!');
        }
        
        $var->status =  1;
    
        $var->save();

        if(isset($creditnote->date_delivery_note) || isset($creditnote->date_billing)){
            $this->recalculatecreditnote($creditnote->id);
        }

        $historial_creditnote = new HistorialcreditnoteController();

        $historial_creditnote->registerAction($var,"creditnote_product","Registró un Producto");


        return redirect('creditnotes/register/'.$var->id_creditnote.'/'.$coin.'')->withSuccess('Producto agregado Exitosamente!');
    }
   
    public function edit($id)
    {
        $creditnote = CreditNote::on(Auth::user()->database_name)->find($id);
    
        return view('admin.credit_notes.edit',compact('creditnote'));
    
    }
    public function editcreditnoteproduct($id,$coin = null)
    {
            $creditnote_product = creditnoteProduct::on(Auth::user()->database_name)->find($id);
        
            if(isset($creditnote_product)){

                $inventory= Inventory::on(Auth::user()->database_name)->find($creditnote_product->id_inventory);

                $company = Company::on(Auth::user()->database_name)->find(1);
                //Si la taza es automatica
                if($company->tiporate_id == 1){
                    $bcv = $this->search_bcv();
                }else{
                    //si la tasa es fija
                    $bcv = $company->rate;
                }

                if(!isset($coin)){
                    $coin = 'bolivares';
                }

                if($coin == 'bolivares'){
                    $rate = null;
                }else{
                    $rate = $creditnote_product->rate;
                }

                return view('admin.credit_notes.edit_product',compact('rate','coin','creditnote_product','inventory','bcv'));
            }else{
                return redirect('/creditnotes')->withDanger('No se Encontro el Producto!');
            }
        
        
    
    }
    
    public function update(Request $request, $id)
    {

        $vars =  CreditNote::on(Auth::user()->database_name)->find($id);

        $vars_status = $vars->status;
        $vars_exento = $vars->exento;
        $vars_islr = $vars->islr;
    
        $data = request()->validate([
            
        
            'segment_id'         =>'required',
            'sub_segment_id'         =>'required',
            'unit_of_measure_id'         =>'required',


            'type'         =>'required',
            'description'         =>'required',
        
            'price'         =>'required',
            'price_buy'         =>'required',
            'cost_average'         =>'required',

            'money'         =>'required',
        
            'special_impuesto'         =>'required',
            'status'         =>'required',
        
        ]);

        $var = CreditNote::on(Auth::user()->database_name)->findOrFail($id);

        $var->segment_id = request('segment_id');
        $var->subsegment_id= request('sub_segment_id');
        $var->unit_of_measure_id = request('unit_of_measure_id');

        $var->code_comercial = request('code_comercial');
        $var->type = request('type');
        $var->description = request('description');
        
        $var->price = request('price');
        $var->price_buy = request('price_buy');
    
        $var->cost_average = request('cost_average');
        $var->photo_creditnote = request('photo_creditnote');

        $var->money = request('money');


        $var->special_impuesto = request('special_impuesto');

        if(request('exento') == null){
            $var->exento = "0";
        }else{
            $var->exento = "1";
        }
        if(request('islr') == null){
            $var->islr = "0";
        }else{
            $var->islr = "1";
        }
    

        if(request('status') == null){
            $var->status = $vars_status;
        }else{
            $var->status = request('status');
        }
    
        $var->save();

        $historial_creditnote = new HistorialcreditnoteController();

        $historial_creditnote->registerAction($var,"creditnote","Actualizó la Cotización");


        return redirect('/creditnotes')->withSuccess('Actualizacion Exitosa!');
    }



        

    public function updatecreditnoteproduct(Request $request, $id)
    { 

           
            $data = request()->validate([
                
                'amount'         =>'required',
                'discount'         =>'required',
            
            ]);

            
        
            $var = creditnoteProduct::on(Auth::user()->database_name)->findOrFail($id);

            $price_old = $var->price;
            $amount_old = $var->amount;

            $sin_formato_price = str_replace(',', '.', str_replace('.', '', request('price')));
            $sin_formato_rate = str_replace(',', '.', str_replace('.', '', request('rate')));

            $coin = request('coin');
            $var->rate = $sin_formato_rate;

            if($coin == 'bolivares'){
                $var->price = $sin_formato_price;
            }else{
                $var->price = $sin_formato_price * $sin_formato_rate;
            }
        
            $var->amount = request('amount');
        
            $var->discount = request('discount');
        
            $global = new GlobalController();

            $value_return = $global->check_amount($var->id_creditnote,$var->id_inventory,$var->amount);


            $islr = request('islr');
            if($islr == null){
                $var->retiene_islr = false;
            }else{
                $var->retiene_islr = true;
            }

            $exento = request('exento');
            if($exento == null){
                $var->retiene_iva = false;
            }else{
                $var->retiene_iva = true;
            }

            if($value_return != 'exito'){
                return redirect('creditnotes/creditnoteproduct/'.$var->id.'/'.$coin.'/edit')->withDanger('La cantidad de este producto excede a la cantidad puesta en inventario! ');
            }

        
            $var->save();

            
            if(isset($var->creditnotes['date_delivery_note']) || isset($var->creditnotes['date_billing'])){
                $this->recalculatecreditnote($var->id_creditnote);
            }

            $historial_creditnote = new HistorialcreditnoteController();

            $historial_creditnote->registerAction($var,"creditnote_product","Actualizó el Producto: ".$var->inventories['code']."/ 
            Precio Viejo: ".number_format($price_old, 2, ',', '.')." Cantidad: ".$amount_old."/ Precio Nuevo: ".number_format($var->price, 2, ',', '.')." Cantidad: ".$var->amount);
        
            return redirect('/creditnotes/register/'.$var->id_creditnote.'/'.$coin.'')->withSuccess('Actualizacion Exitosa!');
        
    }


    public function refreshrate($id_creditnote,$coin,$rate)
    { 
        $sin_formato_rate = str_replace(',', '.', str_replace('.', '', $rate));

        $creditnote = CreditNote::on(Auth::user()->database_name)->find($id_creditnote);

        creditnoteProduct::on(Auth::user()->database_name)->where('id_creditnote',$id_creditnote)
                                ->update(['rate' => $sin_formato_rate]);
    

        CreditNote::on(Auth::user()->database_name)->where('id',$id_creditnote)
                                ->update(['bcv' => $sin_formato_rate]);

        $historial_creditnote = new HistorialcreditnoteController();

        $historial_creditnote->registerAction($creditnote,"creditnote","Actualizó la tasa: ".$rate." / tasa antigua: ".number_format($creditnote->bcv, 2, ',', '.'));
        
        return redirect('/creditnotes/register/'.$id_creditnote.'/'.$coin.'')->withSuccess('Actualizacion de Tasa Exitosa!');
    
    }

 
    public function deleteProduct(Request $request)
    {
        
        $creditnote_product = creditnoteProduct::on(Auth::user()->database_name)->find(request('id_creditnote_product_modal')); 
        
        if(isset($creditnote_product) && $creditnote_product->status == "C"){
            
                creditnoteProduct::on(Auth::user()->database_name)
                ->join('inventories','inventories.id','creditnote_products.id_inventory')
                ->join('products','products.id','inventories.product_id')
                ->where(function ($query){
                    $query->where('products.type','MERCANCIA')
                        ->orWhere('products.type','COMBO');
                })
                ->where('creditnote_products.id',$creditnote_product->id)
                ->update(['inventories.amount' => DB::raw('inventories.amount+creditnote_products.amount'), 'creditnote_products.status' => 'X']);
               
                $this->recalculatecreditnote($creditnote_product->id_creditnote);
        }else{
            
            $creditnote_product->status = 'X'; 
            $creditnote_product->save(); 
        }

        $historial_creditnote = new HistorialcreditnoteController();

        $historial_creditnote->registerAction($creditnote_product,"creditnote_product","Se eliminó un Producto");

        return redirect('/creditnotes/register/'.request('id_creditnote_modal').'/'.request('coin_modal').'')->withDanger('Eliminacion exitosa!!');
        
    }

    public function recalculatecreditnote($id_creditnote)
    {
        $creditnote = null;
                 
        if(isset($id_creditnote)){
             $creditnote = CreditNote::on(Auth::user()->database_name)->findOrFail($id_creditnote);
        }else{
            return redirect('/creditnotes')->withDanger('No llega el numero de la cotizacion');
        } 
 
         if(isset($creditnote)){
           
            $inventories_creditnotes = DB::connection(Auth::user()->database_name)->table('products')->join('inventories', 'products.id', '=', 'inventories.product_id')
                                                            ->join('creditnote_products', 'inventories.id', '=', 'creditnote_products.id_inventory')
                                                            ->where('creditnote_products.id_creditnote',$creditnote->id)
                                                            ->whereIn('creditnote_products.status',['1','C'])
                                                            ->select('products.*','creditnote_products.price as price','creditnote_products.rate as rate','creditnote_products.discount as discount',
                                                            'creditnote_products.amount as amount_creditnote','creditnote_products.retiene_iva as retiene_iva_creditnote'
                                                            ,'creditnote_products.retiene_islr as retiene_islr_creditnote')
                                                            ->get(); 

            $total= 0;
            $base_imponible= 0;
            $price_cost_total= 0;

            //este es el total que se usa para guardar el monto de todos los productos que estan exentos de iva, osea retienen iva
            $total_retiene_iva = 0;
            $retiene_iva = 0;

            $total_retiene_islr = 0;
            $retiene_islr = 0;

            foreach($inventories_creditnotes as $var){
                if(isset($coin) && ($coin != 'bolivares')){
                    $var->price =  bcdiv(($var->price / ($var->rate ?? 1)), '1', 2);
                }
                //Se calcula restandole el porcentaje de descuento (discount)
                $percentage = (($var->price * $var->amount_creditnote) * $var->discount)/100;

                $total += ($var->price * $var->amount_creditnote) - $percentage;
                //----------------------------- 

                if($var->retiene_iva_creditnote == 0){

                    $base_imponible += ($var->price * $var->amount_creditnote) - $percentage; 

                }else{
                    $retiene_iva += ($var->price * $var->amount_creditnote) - $percentage; 
                }

                if($var->retiene_islr_creditnote == 1){

                    $retiene_islr += ($var->price * $var->amount_creditnote) - $percentage; 

                }

            
            }

            $rate = null;
            
            if(isset($coin) && ($coin != 'bolivares')){
                $rate = $creditnote->bcv;
            }
           
            $creditnote->amount = $total * ($rate ?? 1);
            $creditnote->base_imponible = $base_imponible * ($rate ?? 1);
            $creditnote->amount_iva = $base_imponible * $creditnote->iva_percentage / 100;
            $creditnote->amount_with_iva = ($creditnote->amount + $creditnote->amount_iva);
            
            $creditnote->save();
           
        }
    }

    public function deletecreditnote(Request $request)
    {
        
        $creditnote = CreditNote::on(Auth::user()->database_name)->find(request('id_creditnote_modal')); 

        $global = new GlobalController();
        $global->deleteAllProducts($creditnote->id);

        //Anticipo::on(Auth::user()->database_name)->where('id_creditnote',$creditnote->id)->delete();

        $creditnote->delete(); 

        $historial_creditnote = new HistorialcreditnoteController();

        $historial_creditnote->registerAction($creditnote,"creditnote","Se eliminó la cotización");

        return redirect('/creditnotes')->withDanger('Eliminacion exitosa!!');
        
    }

    public function reversar_creditnote(Request $request)
    { 
        
        $id_creditnote = $request->id_creditnote_modal;

        $creditnote = CreditNote::on(Auth::user()->database_name)->findOrFail($id_creditnote);

        $exist_multipayment = Multipayment::on(Auth::user()->database_name)
                            ->where('id_creditnote',$creditnote->id)
                            ->first();
                            
        if(empty($exist_multipayment)){
            if($creditnote != 'X'){
                $detail = DetailVoucher::on(Auth::user()->database_name)->where('id_invoice',$id_creditnote)
                ->update(['status' => 'X']);
    
                
                $global = new GlobalController();
                $global->deleteAllProducts($creditnote->id);

                creditnotePayment::on(Auth::user()->database_name)
                                ->where('id_creditnote',$creditnote->id)
                                ->update(['status' => 'X']);
    
                $creditnote->status = 'X';
                $creditnote->save();

                $historial_creditnote = new HistorialcreditnoteController();

                $historial_creditnote->registerAction($creditnote,"creditnote","Se Reversó la Factura");
            }
        }else{
            
            return redirect('/creditnotes/facturado/'.$creditnote->id.'/bolivares/'.$exist_multipayment->id_header.'');
        }
       
        return redirect('invoices')->withSuccess('Reverso de Factura Exitosa!');

    }

    public function reversar_creditnote_multipayment($id_creditnote,$id_header){

        
        if(isset($id_header)){
            $creditnote = CreditNote::on(Auth::user()->database_name)->find($id_creditnote);

            //aqui reversamos todo el movimiento del multipago
            DB::connection(Auth::user()->database_name)->table('detail_vouchers')
            ->join('header_vouchers', 'header_vouchers.id','=','detail_vouchers.id_header_voucher')
            ->where('header_vouchers.id','=',$id_header)
            ->update(['detail_vouchers.status' => 'X' , 'header_vouchers.status' => 'X']);

            //aqui se cambia el status de los pagos
            DB::connection(Auth::user()->database_name)->table('multipayments')
            ->join('creditnote_payments', 'creditnote_payments.id_creditnote','=','multipayments.id_creditnote')
            ->where('multipayments.id_header','=',$id_header)
            ->update(['creditnote_payments.status' => 'X']);

            //aqui aumentamos el inventario y cambiamos el status de los productos que se reversaron
            DB::connection(Auth::user()->database_name)->table('multipayments')
                ->join('creditnote_products', 'creditnote_products.id_creditnote','=','multipayments.id_creditnote')
                ->join('inventories','inventories.id','creditnote_products.id_inventory')
                ->join('products','products.id','inventories.product_id')
                ->where(function ($query){
                    $query->where('products.type','MERCANCIA')
                        ->orWhere('products.type','COMBO');
                })
                ->where('multipayments.id_header','=',$id_header)
                ->update(['inventories.amount' => DB::raw('inventories.amount+creditnote_products.amount') ,
                        'creditnote_products.status' => 'X']);
    

            //aqui le cambiamos el status a todas las facturas a X de reversado
            Multipayment::on(Auth::user()->database_name)
            ->join('creditnotes', 'creditnotes.id','=','multipayments.id_creditnote')
            ->where('id_header',$id_header)->update(['creditnotes.status' => 'X']);

            Multipayment::on(Auth::user()->database_name)->where('id_header',$id_header)->delete();



            $historial_creditnote = new HistorialcreditnoteController();

            $historial_creditnote->registerAction($creditnote,"creditnote","Se Reversó MultiFactura");




            return redirect('invoices')->withSuccess('Reverso de Facturas Multipago Exitosa!');
        }else{
            return redirect('invoices')->withDanger('No se pudo reversar las facturas');
        }
        
    }
    

    public function listinventory(Request $request, $var = null){
        //validar si la peticion es asincrona
        if($request->ajax()){
            try{
                
                $respuesta = Inventory::on(Auth::user()->database_name)->select('id')->where('code',$var)->where('status',1)->get();
                return response()->json($respuesta,200);

            }catch(Throwable $th){
                return response()->json(false,500);
            }
        }
        
    }


}
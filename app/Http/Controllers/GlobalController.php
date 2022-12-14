<?php

namespace App\Http\Controllers;

use App\Anticipo;
use Goutte\Clientg;
use App\ComboProduct;
use App\Company;
use App\Product;
use App\ExpensePayment;
use App\ExpensesDetail;
use App\Inventory;
use App\QuotationPayment;
use App\QuotationProduct;
use Carbon\Carbon;
use App\UserAccess;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GlobalController extends Controller
{

    
    public function procesar_anticipos($quotation,$total_pay)
    {
        
        if($total_pay >= 0){
            $anticipos_old = DB::connection(Auth::user()->database_name)->table('anticipos')
                                ->where('id_client', '=', $quotation->id_client)
                                ->where(function ($query) use ($quotation){
                                    $query->where('id_quotation',null)
                                        ->orWhere('id_quotation',$quotation->id);
                                })
                                ->where('status', '=', '1')->get();

            foreach($anticipos_old as $anticipo){
                DB::connection(Auth::user()->database_name)->table('anticipo_quotations')->insert(['id_quotation' => $quotation->id,'id_anticipo' => $anticipo->id]);
            } 


            /*Verificamos si el cliente tiene anticipos activos */
            DB::connection(Auth::user()->database_name)->table('anticipos')
                    ->where('id_client', '=', $quotation->id_client)
                    ->where(function ($query) use ($quotation){
                        $query->where('id_quotation',null)
                            ->orWhere('id_quotation',$quotation->id);
                    })
                    ->where('status', '=', '1')
                    ->update(['status' => 'C']);

            //los que quedaron en espera, pasan a estar activos
            DB::connection(Auth::user()->database_name)->table('anticipos')->where('id_client', '=', $quotation->id_client)
            ->where(function ($query) use ($quotation){
                $query->where('id_quotation',null)
                    ->orWhere('id_quotation',$quotation->id);
            })
            ->where('status', '=', 'M')
            ->update(['status' => '1']);
        }
    }

    public function procesar_anticipos_expense($expense,$total_pay)
    {
        
        if($total_pay >= 0){
            
            $anticipos_old = DB::connection(Auth::user()->database_name)->table('anticipos')
                                ->where('id_provider', '=', $expense->id_provider)
                                ->where(function ($query) use ($expense){
                                    $query->where('id_expense',null)
                                        ->orWhere('id_expense',$expense->id);
                                })
                                ->where('status', '=', '1')->get();

            foreach($anticipos_old as $anticipo){
                DB::connection(Auth::user()->database_name)->table('anticipo_expenses')->insert(['id_expense' => $expense->id,'id_anticipo' => $anticipo->id]);
            } 


            /*Verificamos si el proveedor tiene anticipos activos */
            DB::connection(Auth::user()->database_name)->table('anticipos')
                    ->where('id_provider', '=', $expense->id_provider)
                    ->where(function ($query) use ($expense){
                        $query->where('id_expense',null)
                            ->orWhere('id_expense',$expense->id);
                    })
                    ->where('status', '=', '1')
                    ->update(['status' => 'C']);

            //los que quedaron en espera, pasan a estar activos
            DB::connection(Auth::user()->database_name)->table('anticipos')->where('id_provider', '=', $expense->id_provider)
            ->where(function ($query) use ($expense){
                $query->where('id_expense',null)
                    ->orWhere('id_expense',$expense->id);
            })
            ->where('status', '=', 'M')
            ->update(['status' => '1']);
        }
    }

    public function check_anticipo($quotation,$total_pay)
    {
        
            $anticipos = DB::connection(Auth::user()->database_name)->table('anticipos')->where('id_client', '=', $quotation->id_client)
                                                                                    ->where(function ($query) use ($quotation){
                                                                                        $query->where('id_quotation',null)
                                                                                            ->orWhere('id_quotation',$quotation->id);
                                                                                    })
                                                                                    ->where('status', '=', '1')->get();

            foreach($anticipos as $anticipo){

                //si el anticipo esta en dolares, multiplico los dolares por la tasa de la cotizacion, para sacar el monto real en bolivares
                if($anticipo->coin != "bolivares"){
                    $anticipo->amount = ($anticipo->amount / $anticipo->rate) * $quotation->bcv;
                }

                if($total_pay >= $anticipo->amount){
                    DB::connection(Auth::user()->database_name)->table('anticipos')
                                                                ->where('id', $anticipo->id)
                                                                ->update(['status' => 'C']);
                   
                    DB::connection(Auth::user()->database_name)->table('anticipo_quotations')->insert(['id_quotation' => $quotation->id,'id_anticipo' => $anticipo->id]);
                                                         
                    $total_pay -= $anticipo->amount;
                }else{

                    DB::connection(Auth::user()->database_name)->table('anticipos')
                                                                ->where('id', $anticipo->id)
                                                                ->update(['status' => 'C']);
                                                    
                    DB::connection(Auth::user()->database_name)->table('anticipo_quotations')->insert(['id_quotation' => $quotation->id,'id_anticipo' => $anticipo->id]);
                      

                    $amount_anticipo_new = $anticipo->amount - $total_pay;

                    $var = new Anticipo();
                    $var->setConnection(Auth::user()->database_name);
                    
                    $var->id_anticipo_restante = $anticipo->id;
                    $var->date = $quotation->date_billing;
                    $var->id_client = $quotation->id_client;
                    $user       =   auth()->user();
                    $var->id_user = $user->id;
                    $var->id_account = $anticipo->id_account;
                    $var->coin = $anticipo->coin;
                    $var->amount = $amount_anticipo_new;
                    $var->rate = $quotation->bcv;
                    $var->reference = $anticipo->reference;
                    $var->status = 1;
                    $var->save();
                    break;
                }
            }

            
    }

    public function checkAnticipoExpense($expense,$total_pay)
    {
        
            $anticipos = DB::connection(Auth::user()->database_name)->table('anticipos')->where('id_provider', '=', $expense->id_provider)
                                                                                    ->where(function ($query) use ($expense){
                                                                                        $query->where('id_expense',null)
                                                                                            ->orWhere('id_expense',$expense->id);
                                                                                    })
                                                                                    ->where('status', '=', '1')->get();

            foreach($anticipos as $anticipo){

                //si el anticipo esta en dolares, multiplico los dolares por la tasa de la cotizacion, para sacar el monto real en bolivares
                if($anticipo->coin != "bolivares"){
                    $anticipo->amount = ($anticipo->amount / $anticipo->rate) * $expense->rate;
                }

                if($total_pay >= $anticipo->amount){
                    DB::connection(Auth::user()->database_name)->table('anticipos')
                                                                ->where('id', $anticipo->id)
                                                                ->update(['status' => 'C']);
                   
                    DB::connection(Auth::user()->database_name)->table('anticipo_expenses')->insert(['id_expense' => $expense->id,'id_anticipo' => $anticipo->id]);
                                                         
                    $total_pay -= $anticipo->amount;
                }else{

                    DB::connection(Auth::user()->database_name)->table('anticipos')
                                                                ->where('id', $anticipo->id)
                                                                ->update(['status' => 'C']);
                                                    
                    DB::connection(Auth::user()->database_name)->table('anticipo_expenses')->insert(['id_expense' => $expense->id,'id_anticipo' => $anticipo->id]);
                      

                    $amount_anticipo_new = $anticipo->amount - $total_pay;

                    $var = new Anticipo();
                    $var->setConnection(Auth::user()->database_name);

                    $var->id_anticipo_restante = $anticipo->id;
                    $var->date = $expense->date;
                    $var->id_provider = $expense->id_provider;
                    $user       =   auth()->user();
                    $var->id_user = $user->id;
                    $var->id_account = $anticipo->id_account;
                    $var->coin = $anticipo->coin;
                    $var->amount = $amount_anticipo_new;
                    $var->rate = $anticipo->rate;
                    $var->reference = $anticipo->reference;
                    $var->status = 1;
                    $var->save();
                    break;
                }
            }
    }
   
    public function associate_anticipos_quotation($quotation){

        $anticipos = DB::connection(Auth::user()->database_name)->table('anticipos')->where('id_client', '=', $quotation->id_client)
        ->where(function ($query) use ($quotation){
            $query->where('id_quotation',null)
                ->orWhere('id_quotation',$quotation->id);
        })
        ->where('status', '=', '1')->get();

        foreach($anticipos as $anticipo){
            DB::connection(Auth::user()->database_name)->table('anticipo_quotations')->insert(['id_quotation' => $quotation->id,'id_anticipo' => $anticipo->id]);
        }
                  
    }

    public function associate_anticipos_expense($expense){

        $anticipos = DB::connection(Auth::user()->database_name)->table('anticipos')->where('id_provider', '=', $expense->id_provider)
        ->where(function ($query) use ($expense){
            $query->where('id_expense',null)
                ->orWhere('id_expense',$expense->id);
        })
        ->where('status', '=', '1')->get();

        foreach($anticipos as $anticipo){
            DB::connection(Auth::user()->database_name)->table('anticipo_expenses')->insert(['id_expense' => $expense->id,'id_anticipo' => $anticipo->id]);
        }
                  
    }

    public function check_anticipo_multipayment($quotation,$quotations_id,$total_pay)
    {
        
            $anticipos = DB::connection(Auth::user()->database_name)->table('anticipos')->where('id_client', '=', $quotation->id_client)
                                                                                    ->where(function ($query) use ($quotations_id){
                                                                                        $query->where('id_quotation',null)
                                                                                            ->orWhereIn('id_quotation', $quotations_id);
                                                                                    })
                                                                                    ->where('status', '=', '1')->get();
            

            foreach($anticipos as $anticipo){

                //si el anticipo esta en dolares, multiplico los dolares por la tasa de la cotizacion, para sacar el monto real en bolivares
                if($anticipo->coin != "bolivares"){
                    $anticipo->amount = ($anticipo->amount / $anticipo->rate) * $quotation->bcv;
                }

                if($total_pay >= $anticipo->amount){
                    DB::connection(Auth::user()->database_name)->table('anticipos')
                                                                ->where('id', $anticipo->id)
                                                                ->update(['status' => 'C']);
                   
                    DB::connection(Auth::user()->database_name)->table('anticipo_quotations')->insert(['id_quotation' => $quotation->id,'id_anticipo' => $anticipo->id]);
                                                         
                    $total_pay -= $anticipo->amount;
                }else{

                    DB::connection(Auth::user()->database_name)->table('anticipos')
                                                                ->where('id', $anticipo->id)
                                                                ->update(['status' => 'C']);
                                                    
                    DB::connection(Auth::user()->database_name)->table('anticipo_quotations')->insert(['id_quotation' => $quotation->id,'id_anticipo' => $anticipo->id]);
                      

                    $amount_anticipo_new = $anticipo->amount - $total_pay;

                    $var = new Anticipo();
                    $var->setConnection(Auth::user()->database_name);
                    
                    $var->date = $quotation->date_billing;
                    $var->id_client = $quotation->id_client;
                    $user       =   auth()->user();
                    $var->id_user = $user->id;
                    $var->id_account = $anticipo->id_account;
                    $var->coin = $anticipo->coin;
                    $var->amount = $amount_anticipo_new;
                    $var->rate = $quotation->bcv;
                    $var->reference = $anticipo->reference;
                    $var->status = 1;
                    $var->save();
                    break;
                }
            }

            
    }

   
    public function discount_inventory($id_quotation,$sucursal = 1)
    {


        /*Luego, descuenta del Inventario*/
        $inventories_quotations = DB::connection(Auth::user()->database_name)->table('products')
        ->join('quotation_products', 'products.id', '=', 'quotation_products.id_inventory')
        ->where('quotation_products.id_quotation',$id_quotation)
        ->where('quotation_products.status','1')
        ->select('products.*','quotation_products.id as id_quotation','quotation_products.discount as discount',
        'quotation_products.amount as amount_quotation')
        ->get(); 

        foreach($inventories_quotations as $inventories_quotation){

            $quotation_product = QuotationProduct::on(Auth::user()->database_name)->findOrFail($inventories_quotation->id_quotation);

            if(isset($quotation_product))
            {
                $inventory = Product::on(Auth::user()->database_name)->findOrFail($quotation_product->id_inventory);
                if(isset($inventory)){
                    if(($inventories_quotation->type == 'MERCANCIA') || (($inventories_quotation->type == 'COMBO')) && ($inventory-> amount > 0))
                    {
                        //REVISO QUE SEA MAYOR EL MONTO DEL INVENTARIO Y LUEGO DESCUENTO
                        $global = new GlobalController;
                        $inventory->amount = $global->consul_prod_invt($inventory->id);   
                        
                        if($inventory->amount >= $quotation_product->amount){
                            
                        }else{
                            return 'El Inventario de Codigo: '.$inventory->code.' no tiene Cantidad suficiente!';
                        }
                    }else if(($inventories_quotation->type == 'COMBO') && ($inventory-> amount == 0)){
                        $global = new GlobalController;
                        $global->discountCombo($inventory,$quotation_product->amount);
                    }
                    
            }else{
                return 'El Inventario no existe!';
            }
                //CAMBIAMOS EL ESTADO PARA SABER QUE ESE PRODUCTO YA SE COBRO Y SE RESTO DEL INVENTARIO
                $quotation_product->status = 'C';  
                $quotation_product->save();
            }else{
            return 'El Inventario de la cotizacion no existe!';
            }

        }

        return "exito";

    }

    public function check_product($id_quotation,$id_inventory,$amount_new){
        
        $inventories_quotations = DB::connection(Auth::user()->database_name)
        ->table('products')
        ->join('inventories', 'products.id', '=', 'inventories.product_id')
        ->where('inventories.id',$id_inventory)
        ->select('products.*','inventories.amount as amount_inventory')
        ->first(); 

        if(isset($inventories_quotations) && ($inventories_quotations->type == "MERCANCIA"))
        {
            return $this->check_amount($id_quotation,$inventories_quotations,$amount_new);

        }else if(isset($inventories_quotations) && ($inventories_quotations->type == "COMBO") && ($inventories_quotations->amount_inventory == 0))
        {
            return $this->check_combo_by_zero($id_quotation,$inventories_quotations,$amount_new);

        }else if(isset($inventories_quotations) && ($inventories_quotations->type == "COMBO") ){

            return $this->check_amount($id_quotation,$inventories_quotations,$amount_new);

        }

        return "exito";

    }
    public function check_amount($id_quotation,$inventories_quotations,$amount_new)
    {
        
        //si es un servicio no se chequea que posea inventario, ni tampoco el combo, el combo se revisa sus componentes si tienen inventario
       /* if(isset($inventories_quotations) && ((($inventories_quotations->type == "MERCANCIA")) || (($inventories_quotations->type == "COMBO")))){
            $inventory = Inventory::on(Auth::user()->database_name)->find($inventories_quotations->id);

            $sum_amount = DB::connection(Auth::user()->database_name)->table('quotation_products')
                            ->where('id_quotation',$id_quotation)
                            ->where('id_inventory',$inventories_quotations->id)
                            ->where("status",'1')
                            ->sum('amount');

            $comboController = new ComboController();

            $suma_en_combos = 0;

            $suma_en_combos = $comboController->check_exist_combo_in_quotation($id_quotation,$inventory->product_id);
         
            
            $total_in_quotation = $sum_amount + $amount_new;
         
           
            if ($inventory->amount >= ($total_in_quotation + $suma_en_combos)){
                return "exito";
            }else{
                return "El producto ".$inventories_quotations->description." no tiene inventario suficiente";
            } 

        }else{*/
            return "exito";
     //   }
    
    }

    public function check_combo_by_zero($id_quotation,$inventories_quotations,$amount_new){

        
        $relation_combo = ComboProduct::on(Auth::user()->database_name)->where("id_combo",$inventories_quotations->id)->get();

        
        if(isset($relation_combo) && (count($relation_combo) > 0)){
            
            foreach($relation_combo as $relation){
                $inventories_quotations = DB::connection(Auth::user()->database_name)
                                                                    ->table('products')
                                                                    ->where('id',$relation->id_product)
                                                                    ->select('products.*')
                                                                    ->first(); 

                $value_return = $this->check_amount($id_quotation,$inventories_quotations,$amount_new * $relation->amount_per_product);
                
                if($value_return != "exito"){
                    return "El producto ".$inventories_quotations->description." del combo no tiene inventario suficiente";
                }
            }
            return "exito";
        }else{
           
            return "El combo no tiene Productos Asociados";
        }
        
    }


    public function check_all_products_after_facturar($id_quotation){

        $all_products_quotation = DB::connection(Auth::user()->database_name)->table('inventories')
                                    ->join('quotation_products', 'quotation_products.id_inventory','=','inventories.id')
                                    ->join('products', 'products.id','=','inventories.product_id')
                                    ->where('quotation_products.id_quotation',$id_quotation)
                                    ->where('quotation_products.status','1')
                                    ->where(function ($query){
                                        $query->where('products.type','MERCANCIA');
                                        $query->orWhere('products.type','COMBO');
                                    })
                                    ->select('inventories.code as code','inventories.id as id_inventory','quotation_products.id_quotation as id_quotation','quotation_products.discount as discount',
                                    'quotation_products.amount as amount_quotation')
                                    ->get(); 

       
        foreach($all_products_quotation as $product){
            $value_return = $this->check_product($id_quotation,$product->id_inventory,0);

            if($value_return != "exito"){
                return $value_return;
            }
        }

        return "exito";

    }


    public function add_payment($quotation,$id_account,$payment_type,$amount,$bcv){
        $var = new QuotationPayment();
        $var->setConnection(Auth::user()->database_name);

        $var->id_quotation = $quotation->id;
        $var->id_account = $id_account;
   
        $var->payment_type = $payment_type;
        $var->amount = $amount;
        
        
        $var->rate = $bcv;
        
        $var->status =  1;
        $var->save();
        
        return $var->id;
    }

    public function add_payment_expense($expense,$id_account,$payment_type,$amount,$bcv){
        $var = new ExpensePayment();
        $var->setConnection(Auth::user()->database_name);

        $var->id_expense = $expense->id;
        $var->id_account = $id_account;
   
        $var->payment_type = $payment_type;
        $var->amount = $amount;
        
        $var->status =  1;
        $var->save();
        
        return $var->id;
    }

    public function aumentCombo($inventory,$amount_discount)
    {
        $product = ComboProduct::on(Auth::user()->database_name)
                    ->join('products','products.id','combo_products.id_product')
                    ->join('inventories','inventories.product_id','products.id')
                    ->where('combo_products.id_combo',$inventory->product_id)
                    ->update(['inventories.amount' => DB::raw('inventories.amount - (combo_products.amount_per_product *'.$amount_discount.')')]);


    }

    public function discountCombo($inventory,$amount_discount)
    {
        $product = ComboProduct::on(Auth::user()->database_name)
                    ->join('products','products.id','combo_products.id_product')
                    ->join('inventories','inventories.product_id','products.id')
                    ->where('combo_products.id_combo',$inventory->product_id)
                    ->update(['inventories.amount' => DB::raw('inventories.amount - (combo_products.amount_per_product *'.$amount_discount.')')]);


    }

    function asignar_payment_type($type){
      
        if($type == 1){
            return "Cheque";
        }
        if($type == 2){
            return "Contado";
        }
        if($type == 3){
            return "Contra Anticipo";
        }
        if($type == 4){
            return "Cr??dito";
        }
        if($type == 5){
            return "Dep??sito Bancario";
        }
        if($type == 6){
            return "Efectivo";
        }
        if($type == 7){
            return "Indeterminado";
        }
        if($type == 8){
            return "Tarjeta Coorporativa";
        }
        if($type == 9){
            return "Tarjeta de Cr??dito";
        }
        if($type == 10){
            return "Tarjeta de D??bito";
        }
        if($type == 11){
            return "Transferencia";
        }
    }

    public function deleteAllProducts($id_quotation)
    {
        $quotation_products = QuotationProduct::on(Auth::user()->database_name)->where('id_quotation',$id_quotation)->get(); 
        
        if(isset($quotation_products)){
            foreach($quotation_products as $quotation_product){
                if(isset($quotation_product) && $quotation_product->status == "C"){
                    QuotationProduct::on(Auth::user()->database_name)
                        ->join('inventories','inventories.id','quotation_products.id_inventory')
                        ->join('products','products.id','inventories.product_id')
                        ->where(function ($query){
                            $query->where('products.type','MERCANCIA')
                                ->orWhere('products.type','COMBO');
                        })
                        ->where('quotation_products.id',$quotation_product->id)
                        ->update(['inventories.amount' => DB::raw('inventories.amount+quotation_products.amount'), 'quotation_products.status' => 'X']);
                }
            }
        }
    }

    public function deleteAllProductsExpense($id_expense)
    {
        
        $expense_products = ExpensesDetail::on(Auth::user()->database_name)->where('id_expense',$id_expense)->get(); 
        
        
        if(isset($expense_products)){
            foreach($expense_products as $expense_product){
                if(isset($expense_product) && $expense_product->status == "C"){
                    ExpensesDetail::on(Auth::user()->database_name)
                        ->join('inventories','inventories.id','expenses_details.id_inventory')
                        ->join('products','products.id','inventories.product_id')
                        ->where(function ($query){
                            $query->where('products.type','MERCANCIA')
                                ->orWhere('products.type','COMBO');
                        })
                        ->where('expenses_details.id',$expense_product->id)
                        ->update(['inventories.amount' => DB::raw('inventories.amount-expenses_details.amount'), 'expenses_details.status' => 'X']);
                }
            }
        }
    }     

    public function search_bcv()
    {

        $company = Company::on("logins")->where('login',Auth::user()->database_name)->first();
        $date = Carbon::now();
        $datenow = $date->format('Y-m-d');    
        $clientg = new Clientg();

        if($company->date_consult_bcv != $datenow){
           
                $url = "http://www.bcv.org.ve/bcv/contactos";
            
                $ch = curl_init( $url );
                // Establecer un tiempo de espera
                curl_setopt( $ch, CURLOPT_TIMEOUT, 3 );
                curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 3 );
                // Establecer NOBODY en true para hacer una solicitud tipo HEAD
                curl_setopt( $ch, CURLOPT_NOBODY, true );
                // Permitir seguir redireccionamientos
                curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
                // Recibir la respuesta como string, no output
                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
                // Descomentar si tu servidor requiere un user-agent, referrer u otra configuraci??n espec??fica
                // $agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36';
                // curl_setopt($ch, CURLOPT_USERAGENT, $agent)
                $data = curl_exec( $ch );
                // Obtener el c??digo de respuesta
                $httpcode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
                //cerrar conexi??n
                curl_close( $ch );
                // Aceptar solo respuesta 200 (Ok), 301 (redirecci??n permanente) o 302 (redirecci??n temporal)
                $accepted_response = array( 200, 301, 302 );
                if( in_array( $httpcode, $accepted_response ) ) {
                    $urlexists = true;
                } else {
                    $urlexists = false;
                }
                if ($urlexists == true) { // condicion para validar consulta 
                    $crawler = $clientg->request('GET', 'http://www.bcv.org.ve/bcv/contactos');
                } else {
                    $crawler = '';   
                }

            if ($crawler != '') {

               $contact = $crawler->filter("[class='col-sm-6 col-xs-6 centrado']")->last();      
               
               if (count($contact) > 0) {

                   $rateconsult = $contact->text();
                   $bcv = str_replace(',', '.', str_replace('.', '',$rateconsult));
                   $bcv = bcdiv($bcv, '1', 2);  

                   $companies  = Company::on("logins")->findOrFail($company->id);  // guardar taza
                   $companies->rate_bcv = $bcv;
                   $companies->date_consult_bcv = $datenow;
                   $companies->save();

               } else {
                   $bcv = $company->rate_bcv; 
               }

            } else {
               $bcv = $company->rate_bcv;    
            }

            /*-------------------------- */
            return bcdiv($bcv, '1', 2);

        }else{
            
            if($company->tiporate_id == 1){
                if($company->rate_bcv != 0){
                    return $company->rate_bcv;
                }else{
                    return 1;
                }
            }else{
                if($company->rate_bcv != 0){
                    return $company->rate;
                }else{
                    return 1;
                }
            }
        }
    }

    public function data_last_month_day() { 
        $month = date('m');
        $year = date('Y');
        $day = date("d", mktime(0,0,0, $month+1, 0, $year));
   
        return date('Y-m-d', mktime(0,0,0, $month, $day, $year));
    }
   
    /** Actual month first day **/
    public function data_first_month_day() {
        $month = date('m');
        $year = date('Y');
        $dia = date('1');
        return date('Y-m-').'01';
    }  



    function consul_prod_invt($id_product,$sucursal = 1){ // buscar solo la cantidad actual del producto

        if ($sucursal == 1) {
            $inventories_quotations = DB::connection(Auth::user()->database_name)
            ->table('inventory_histories')
            ->where('id_product','=',$id_product)
            ->select('amount_real')
            ->get()->last(); 
        } else {
            $inventories_quotations = DB::connection(Auth::user()->database_name)
            ->table('inventory_histories')
            ->where('id_product','=',$id_product)
            ->where('id_branch','=',$sucursal)
            ->select('amount_real')
            ->get()->last();
        }
    
        if (empty($inventories_quotations)) {
        $amount_real =0;
        } else {
            
            //$amount_real = 888;
             
            if ($inventories_quotations->amount_real > 0) {

            $amount_real = $inventories_quotations->amount_real;

            } else {
            
            $amount_real = 0;  
            
            }
        
        }
    
        return $amount_real;
    }
    
    
    function transaction_inv($type,$id_product,$description = '-',$amount = 0,$price = 0,$date,$branch = 1,$centro_cost = 1,$delivery_note = 0,$id_historial_inv = 0,$id,$quotation = 0,$expense = null){
    
        $msg = 'Sin Registro';   
    
       // $product = Inventory::on(Auth::user()->database_name)->where('id',$id_inventary)->get();
    
            if ($branch == 1) { // todas las sucurssales
                $inventories_quotations = DB::connection(Auth::user()->database_name)
                ->table('inventory_histories')
                ->where('id_product','=',$id_product)
                ->select('*')
                ->get()->last();
            } else { // sucursal especifica
                $inventories_quotations = DB::connection(Auth::user()->database_name)
                ->table('inventory_histories')
                ->where('id_product','=',$id_product)
                ->where('id_branch','=',$branch)
                ->select('*')
                ->get()->last();	
            }

                if (empty($inventories_quotations)) {
                    $msg = 'El Producto no tiene inventario o no existe.';
                    $amount_real = 0;
                } else {
                    
                    $amount_real = $inventories_quotations->amount_real;

                }

                if ($date == null) {

                $date = Carbon::now();
                $date = $date->format('Y-m-d'); 
                
                } else {

                $date = date("Y-m-d",strtotime($date)); // validando date y convirtiendo a formato de la base de datos Y-m-d
                
                }

            $transaccion = 0;
            $agregar = 'true';
    
            if ($amount > 0 ) {
    
                switch ($type) {
                    case 'compra':
                        
                        if ($id_historial_inv != 0) {
       
                            $inventories_quotations_hist = DB::connection(Auth::user()->database_name)
                            ->table('inventory_histories')
                            ->where('id','=',$id_historial_inv)
                            ->select('id','amount')
                            ->get()->last();  
                        

                            if (!empty($inventories_quotations_hist)) {
                        
                                
                                if ($inventories_quotations_hist->amount == $amount) {
                                
                                    $transaccion = $amount_real;
                                    $agregar = 'false';   
                                } else {
                                    	
                                    $transaccion = ($amount_real+$inventories_quotations_hist->amount)-$amount;	
                                    $agregar = 'true'; 
                                    $type = 'compra';                                    
                                
                                }
    
                                
                            } else {
                                $transaccion = $amount_real+$amount;  
                            }
                        } else {

                            $transaccion = $amount_real+$amount; 
                        }

                    break;
                    case 'venta':
    
                    if ($id_historial_inv != 0) {
                        $inventories_quotations_hist = DB::connection(Auth::user()->database_name)
                        ->table('inventory_histories')
                        ->where('id','=',$id_historial_inv)
                        ->select('id','amount')
                        ->get()->last();  
                        
                            if (!empty($inventories_quotations_hist)) {
                                
                                    $amount = 0;
                                    $transaccion = $amount_real;
                                    $description = 'De Nota a Factura'; 
                            }
    
                    } else {
                    $transaccion = $amount_real-$amount;
                    }    
                    break;          
                    case 'entrada':
                    $transaccion = $amount_real+$amount;
                    break;      
                    case 'salida':
                    $transaccion = $amount_real-$amount;
                    break;
                    case 'nota':
                            
                        if ($id_historial_inv != 0) {
       
                            $inventories_quotations_hist = DB::connection(Auth::user()->database_name)
                            ->table('inventory_histories')
                            ->where('id','=',$id_historial_inv)
                            ->select('id','amount')
                            ->get()->last();  
                        

                            if (!empty($inventories_quotations_hist)) {
                        
                                
                                if ($inventories_quotations_hist->amount == $amount) {
                                    $amount_nota = 0;
                                    $transaccion = $amount_real;
                                    $agregar = 'false';   
                                } else {

                                    $transaccion = ($amount_real+$inventories_quotations_hist->amount)-$amount;	
                                    $agregar = 'true'; 
                                    $type = 'aju_nota';                                    
                                
                                }
    
                                
                            } else {
                                $transaccion = $amount_real-$amount;  
                            }
    
                        } else {
                                $transaccion = $amount_real-$amount; 
                        }
    
                    break;               
                    case 'rev_nota':
                    $transaccion = $amount_real+$amount;
                    break;
                    case 'aju_nota':
                        if ($id_historial_inv != 0) {
       
                            $inventories_quotations_hist = DB::connection(Auth::user()->database_name)
                            ->table('inventory_histories')
                            ->where('id','=',$id_historial_inv)
                            ->select('id','amount')
                            ->get()->last();  
                        

                            if (!empty($inventories_quotations_hist)) {
                        
                                
                                if ($inventories_quotations_hist->amount == $amount) {
                                    $amount_nota = 0;
                                    $transaccion = $amount_real;
                                    $agregar = 'false';   
                                } else {

                                    $transaccion = ($amount_real+$inventories_quotations_hist->amount)-$amount;	
                                    $agregar = 'true'; 
                                    $type = 'aju_nota';                                    
                                
                                }
    
                                
                            } else {
                                $transaccion = $amount_real-$amount;  
                            }
    
                        } else {
                                $transaccion = $amount_real-$amount; 
                        }
                    break;  

                    case 'aju_compra':
                        if ($id_historial_inv != 0) {
       
                            $inventories_quotations_hist = DB::connection(Auth::user()->database_name)
                            ->table('inventory_histories')
                            ->where('id','=',$id_historial_inv)
                            ->select('id','amount')
                            ->get()->last();  
                        

                            if (!empty($inventories_quotations_hist)) {
                        
                                
                                if ($inventories_quotations_hist->amount == $amount) {
                                    $amount_nota = 0;
                                    $transaccion = $amount_real;
                                    $agregar = 'false';   
                                } else {

                                    $transaccion = ($amount_real-$inventories_quotations_hist->amount)+$amount;	
                                    $agregar = 'true'; 
                                    $type = 'aju_compra';                                    
                                
                                }
    
                                
                            } else {
                                $transaccion = $amount_real;
                                $agregar = 'false';  
                            }
    
                        } else {
                                $transaccion = $amount_real;
                                $agregar = 'false'; 
                        }
                    break;
                    case 'rev_venta':
                    $transaccion = $amount_real+$amount;
                    break;  
                    case 'rev_compra':
                    $transaccion = $amount_real-$amount;
                    break;  
    
                }
    
    
                    if ($transaccion < 0) {
    
                       $msg = "La cantidad es mayor a la disponible en inventario";
                
                    } else {
    
                        $user       =   auth()->user();
                    
                        if ($agregar == 'true') {
                            

                             DB::connection(Auth::user()->database_name)->table('inventory_histories')->insert([
                            'id_product' => $id_product,
                            'id_user' => $user->id,
                            'id_branch' => $branch,
                            'id_centro_costo' => $branch,
                            'id_quotation_product' => $quotation,
                            'id_expense_detail' => $expense,
                            'date' => $date,
                            'type' => $type,
                            'price' => $price,
                            'amount' => $amount,
                            'amount_real' => $transaccion,
                            'status' => 'A']);
                            
                            $id_last = DB::connection(Auth::user()->database_name)
                            ->table('inventory_histories')
                            ->select('id')
                            ->get()->last();                             

                                if ($type == 'nota' || $type == 'factura' || $type == 'aju_nota'){
                                    DB::connection(Auth::user()->database_name)->table('quotation_products')
                                    ->where('id','=',$id)
                                    ->update(['id_inventory_histories' => $id_last->id]);
                                }
       
                                if ($type == 'compra' || $type == 'aju_compra'){
                                    DB::connection(Auth::user()->database_name)->table('expenses_details')
                                    ->where('id','=',$id)
                                    ->update(['id_inventory_histories' => $id_last->id]);
                                }      
                        
                        }
    
                        switch ($type) {
                            case 'compra':
                            $msg = 'La Compra fue registrada con exito';
                            break;
                            case 'venta';
                            $msg = 'La Venta fue registrada con exito';
                            break;
                            case 'nota':
                            $msg = 'exito';//'La Nota fue registrada con exito';
                            break;  
                            case 'rev_nota':
                            $msg = 'Reverso de Nota exitoso';
                            break;   
                            case 'aju_nota':
                            $msg = 'Eliminacion de producto de la Nota exitoso';
                            break;   
                            case 'aju_compra':
                            $msg = 'Ajuste de producto de Compra exitoso';
                            break;      
                            case 'rev_venta':
                            $msg = 'Reverso de Factura exitoso';
                            break;                                           
                            case 'entrada':
                            $msg = 'Agregado a inventario exitosamente';
                            break;
                            case 'salida':
                            $msg = 'Salida de inventario exitoso';
                            break;
                            default:
                            $msg = 'La operacion no es valida';
                            break;
                        }
                    }
    
            } else { // condicion cantidad
                if($type == 'creado') {
                    
                    $user       =   auth()->user();





                     DB::connection(Auth::user()->database_name)->table('inventory_histories')->insert([
                    'id_product' => $id_product,
                    'id_user' => $user->id,
                    'id_branch' => 1,
                    'id_centro_costo' => 1,
                    'id_quotation_product' => 0,
                    'id_expense_detail' => 0,
                    'date' => $date,
                    'type' => $type,
                    'price' => $price,
                    'amount' => 0,
                    'amount_real' => 0,
                    'status' => 'A']);

                    $msg = "Producto Creado";
            
                } else {

                    $msg = "La cantidad de la oprecion debe ser mayor a cero";
                }

            }
    
    return $msg;

    } // fin de funcion transaccion

   // funcion para subir imagenes
    public static function setCaratula($foto,$id = '0',$code_comercial = '0'){

        $fecha_hora = date('dmYhis', time());
    
        if ($foto) {
            $company = Company::on(Auth::user()->database_name)->find(1);

            $imageName = $id.'-'.$code_comercial.'-'.$fecha_hora.'.jpg';
            $imagen = Image::make($foto)->encode('jpg',90);
            $imagen->resize(600,800, function($constraint) {
                $constraint->upsize();
            });
            Storage::disk('public')->put("img/$company->login/productos/$imageName", $imagen->stream()); 
            
            return $imageName;

        }else{
            return 'false';
        }
    }
   // fin funcion para subir imagenes

    // funcion para actualizar imagenes
      public static function setCaratulaup($foto,$id = '0',$code_comercial = '0',$actual = null){
      
        $fecha_hora = date('dmYhis', time());
    
        if ($foto) {
            $company = Company::on(Auth::user()->database_name)->find(1);
            
            Storage::disk('public')->delete("img/$company->login/productos/$actual");

            $imageName = $id.'-'.$code_comercial.'-'.$fecha_hora.'.jpg';
            $imagen = Image::make($foto)->encode('jpg',90);
            $imagen->resize(600,800, function($constraint) {
                $constraint->upsize();
            });
            Storage::disk('public')->put("img/$company->login/productos/$imageName", $imagen->stream());
            
            return $imageName;

        }else{
            return 'false';
        }
    }
   // fin funcion para subir imagenes

   
}

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
 
<title>Nota de Entrega</title>
<style>
  table, td, th {
    border: 1px solid black;
  }
  
  table {
    border-collapse: collapse;
    width: 50%;
  }
  
  th {
    
    text-align: left;
  }
  </style>
</head>

<body>
  <table id="top">
    <tr>
      <th style="text-align: left; font-weight: normal; width: 10%; border-color: white; font-weight: bold;"> <img src="{{ asset(Auth::user()->company->foto_company ?? 'img/logo.jpg') }}" width="100%" height="60" class="d-inline-block align-top" alt="">
      </th>
      <th style="text-align: left; font-weight: normal; width: 90%; border-color: white; font-weight: bold;"><h4>{{Auth::user()->company->razon_social ?? ''}}  <h5>{{Auth::user()->company->code_rif ?? ''}}</h5> </h4></th>    </tr> 
  </table>
  <br>
  <h4 style="color: black">NOTA DE ENTREGA NRO: {{ str_pad($quotation->number_delivery_note ?? $quotation->id, 6, "0", STR_PAD_LEFT)}}</h4>

 
   
 
<table>
  @if (isset($company->franqueo_postal))
  <tr>
    <th style="font-weight: normal; width: 40%;">Concesión Postal:</th>
    <th style="font-weight: normal;">Nº {{ $company->franqueo_postal ?? ''}}</th>
   
  </tr>
  @endif

  <tr>
    <td style="width: 40%;">Fecha de Emisión:</td>
    @if (isset($quotation->date_delivery_note))
      <td>{{date_format(date_create($quotation->date_delivery_note),"d-m-Y")}}</td>
    @else
      <td></td>
    @endif
  </tr>
  
</table>


<table style="width: 100%;">
  <tr>
    <th style="font-weight: normal; font-size: medium;">Nombre / Razón Social: &nbsp;  {{ $quotation->clients['name'] ?? ''}}</th>
    
   
  </tr>
  <tr>
    <td>Domicilio Fiscal: &nbsp;  {{ $quotation->clients['direction'] ?? ''}}
    </td>
    
    
  </tr>
  
</table>




<table style="width: 100%;">
  <tr>
    <th style="text-align: center;">Teléfono</th>
    <th style="text-align: center;">RIF/CI</th>
    <th style="text-align: center;">N° Control / Serie</th>
    <th style="text-align: center;">Condición de Pago</th>
    <th style="text-align: center;">Transp./Tipo Entrega</th>
   
  </tr>
  <tr>
    <td style="text-align: center;">{{ $quotation->clients['phone1'] ?? ''}}</td>
    <td style="text-align: center;">{{ $quotation->clients['type_code'] ?? ''}} {{ $quotation->clients['cedula_rif'] ?? '' }}</td>
    <td style="text-align: center;">{{ $quotation->serie }}</td>
    <td style="text-align: center;">Nota de Entrega</td>
    <td style="text-align: center;">{{ $quotation->transports['placa'] ?? '' }}</td>
    
    
  </tr>
  
</table>

<table style="width: 100%;">
  <tr>
  <th style="font-weight: normal; font-size: medium;">Observaciones: &nbsp; {{ $quotation->observation ?? ''}} </th>
</tr>
  
</table>

<table style="width: 100%;">
  <tr>
    <th style="text-align: center; width: 100%;">Productos</th>
  </tr> 
</table>
<table style="width: 100%;">
  <tr>
    <th style="text-align: center; ">Código</th>
    <th style="text-align: center; ">Descripción</th>
    <th style="text-align: center; ">Cantidad</th>
    <th style="text-align: center; ">P.V.J.</th>
    <th style="text-align: center; ">Desc</th>
    <th style="text-align: center; ">Total</th>
  </tr> 
  @foreach ($inventories_quotations as $var)
      <?php
       
        $percentage = (($var->price * $var->amount_quotation) * $var->discount)/100;

        $total_less_percentage = ($var->price * $var->amount_quotation) - $percentage;

        $total_less_percentage = $total_less_percentage ;
      ?>
    <tr>
      <th style="text-align: center; font-weight: normal;">{{ $var->code_comercial }}</th>
      <th style="text-align: center; font-weight: normal;">{{ $var->description }}</th>
      <th style="text-align: center; font-weight: normal;">{{ number_format($var->amount_quotation, 0, '', '.') }}</th>
      <th style="text-align: center; font-weight: normal;">{{ number_format($var->price , 2, ',', '.')  }}</th>
      <th style="text-align: center; font-weight: normal;">{{ $var->discount }}%</th>
      <th style="text-align: right; font-weight: normal;">{{ number_format($total_less_percentage, 2, ',', '.') }}</th>
    </tr> 
  @endforeach 
</table>


<?php
  $iva = ($quotation->base_imponible * $quotation->iva_percentage)/100;

  $total_bs = $quotation->total_factura + $iva;


  $iva = $iva ;

  $total = $total_bs ;
?>

<table style="width: 100%;">
  <tr>
    <th style="text-align: left; font-weight: normal; width: 38%; border-bottom-color: white; border-right-color: white; font-size: small;"></th>
    <th style="text-align: right; font-weight: normal; width: 79%; border-bottom-color: white;">Sub Total</th>
    <th style="text-align: right; font-weight: normal; width: 21%;">{{ number_format($quotation->total_factura , 2, ',', '.') }}</th>
  </tr> 
  <tr>
    <th style="text-align: left; font-weight: normal; width: 38%; border-bottom-color: white; border-right-color: white; font-size: small;"></th>
    <th style="text-align: right; font-weight: normal; width: 79%; border-bottom-color: white;">Base Imponible</th>
    <th style="text-align: right; font-weight: normal; width: 21%;">{{ number_format($quotation->base_imponible , 2, ',', '.') }}</th>
  </tr> 
  <tr>
    <th style="text-align: left; font-weight: normal; width: 38%; border-bottom-color: white; border-right-color: white; font-size: small;"></th>
    <th style="text-align: right; font-weight: normal; width: 79%; border-bottom-color: white;">Ventas Exentas</th>
    <th style="text-align: right; font-weight: normal; width: 21%;">{{ number_format(($retiene_iva ?? 0) , 2, ',', '.') }}</th>
  </tr> 
  <tr>
    <th style="text-align: left; font-weight: normal; width: 38%; border-bottom-color: white; border-right-color: white; font-size: small;"></th>
    <th style="text-align: right; font-weight: normal; width: 79%; border-bottom-color: white;">I.V.A.{{ $quotation->iva_percentage }}%</th>
    <th style="text-align: right; font-weight: normal; width: 21%;">{{ number_format($iva, 2, ',', '.') }}</th>
  </tr> 
 
  
    @if (isset($coin) && ($coin == 'bolivares'))
      <tr>
        <th style="text-align: left; font-weight: normal; width: 38%; border-bottom-color: white; border-right-color: white; font-size: small;"></th>
        <th style="text-align: right; font-weight: normal; width: 21%; border-bottom-color: white;">TOTAL Bs</th>
        <th style="text-align: right; font-weight: normal; width: 21%;">{{ number_format($total, 2, ',', '.') }}</th>
      </tr> 
      <tr>
        <th style="text-align: left; font-weight: normal; width: 38%; border-bottom-color: white; border-right-color: white; font-size: small;"> Tasa de cambio a la fecha: {{ number_format(bcdiv($quotation->bcv, '1', 2), 2, ',', '.') }} Bs.</th>
        <th style="text-align: right; font-weight: normal; width: 21%; border-bottom-color: white; font-size: small;">TOTAL $</th>
        <th style="text-align: right; font-weight: normal; width: 21%;">{{ number_format($total / $quotation->bcv, 2, ',', '.') }}</th>
      </tr> 
    @else
      <tr>
        <th style="text-align: left; font-weight: normal; width: 38%; border-bottom-color: white; border-right-color: white; font-size: small;"></th>
        <th style="text-align: right; font-weight: normal; width: 21%; border-bottom-color: white;">TOTAL $</th>
        <th style="text-align: right; font-weight: normal; width: 21%;">{{ number_format($total, 2, ',', '.') }}</th>
      </tr> 
      <tr>
        <th style="text-align: left; font-weight: normal; width: 38%; border-bottom-color: white; border-right-color: white; font-size: small;"> Tasa de cambio a la fecha: {{ number_format(bcdiv($quotation->bcv, '1', 2), 2, ',', '.') }} Bs.</th>
        <th style="text-align: right; font-weight: normal; width: 21%; border-bottom-color: white;  border-left-color: black;">TOTAL Bs</th>
        <th style="text-align: right; font-weight: normal; width: 21%; border-bottom-color: white;">{{ number_format($total * $quotation->bcv, 2, ',', '.') }}</th>
      </tr> 
    @endif

  <tr>
    <th style="text-align: left; width: 50%; border-bottom-color: black; border-right-color: white;" ></th>
    <th style="text-align: left; font-weight: normal; width: 15%; border-top-color: rgb(17, 9, 9); border-right-color: black; font-size: small;"></th>
    <th style="text-align: right; font-weight: normal; width: 15%; "></th>
  </tr> 
</table>

</body>
</html>


  
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
 
<title></title>
<style>
  table, td, th {
    border: 1px solid black;
    font-size: x-small;
  }
  
  table {
    border-collapse: collapse;
    width: 100%;
  }
  
  th {
    
    text-align: left;
  }
  </style>
</head>

<body>

  <br>
  <h4 style="color: black; text-align: center">Reporte Anticipos</h4>
 
  <h5 style="color: black; text-align: center">Fecha de Emisión: {{ $date_end ?? $datenow ?? '' }}</h5>
   
  <?php 
    
    $total = 0;
    $total_dolar = 0;
   
  ?>
<table style="width: 100%;">
  <tr>
    <th class="text-center">N°</th>
    <th class="text-center">Cliente</th>
    <th class="text-center">Caja/Banco</th>
    <th class="text-center">Fecha del Anticipo</th>
    <th class="text-center">Referencia</th>
    <th class="text-center">REF</th>
    <th class="text-center">Monto</th>
    <th class="text-center">Moneda</th>
   
</tr>
</thead>

<tbody>
    @if (empty($anticipos))
    @else
        @foreach ($anticipos as $key => $anticipo)
        <?php 

            $amount_bcv = 0;
            
            $amount_bcv = $anticipo->amount / $anticipo->rate;


            if($anticipo->coin != 'bolivares'){
                $anticipo->amount = $anticipo->amount / $anticipo->rate;
            }


            if (isset($anticipo->quotations['number_invoice'])) {
                
                $num_fac = 'Factura: '.$anticipo->quotations['number_invoice'].' Ctrl/Serie: '.$anticipo->quotations['serie'];

            } else {

                if (isset($anticipo->quotations['number_delivery_note'])) {
                
                $num_fac = 'Nota de Entrega: '.$anticipo->quotations['number_delivery_note'].' Ctrl/Serie: '.$anticipo->quotations['serie'];
                
                } else {

                    $num_fac = '';
                }
            }

        ?>
        <tr>
            @if (isset($anticipo->id_anticipo_restante))
                <td class="text-center">{{ $anticipo->id }}<br>{{ (isset($anticipo->id_anticipo_restante)) ? 'Restante de: '.$anticipo->id_anticipo_restante : '' }}</td>
            @else
                <td class="text-center">{{$anticipo->id ?? ''}}</td>
            @endif
        
            <td class="text-center">{{$anticipo->clients['name'] ?? ''}}<br>{{$num_fac}}</td>
            <td class="text-center">{{$anticipo->accounts['description'] ?? ''}}</td>
            <td class="text-center">{{date('d-m-Y',strtotime($anticipo->date)) ?? ''}}</td>
            <td class="text-center">{{$anticipo->reference ?? ''}}</td>
            <td class="text-right">${{number_format($amount_bcv ?? 0, 2, ',', '.')}}</td>
            <td class="text-right">{{number_format($anticipo->amount ?? 0, 2, ',', '.')}}</td>
            <td class="text-center">{{$anticipo->coin ?? ''}}</td>
       
    
        </tr>
        @endforeach
    @endif
</tbody>
</table>

</body>
</html>
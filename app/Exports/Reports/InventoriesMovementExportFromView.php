<?php

namespace App\Exports\Reports;


use App\Http\Controllers\Exports\Reports\InventoriesMovementExportController;
use Carbon\Carbon;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
class InventoriesMovementExportFromView implements FromView
{
    public $request;

    public function __construct($request)
    {
        $this->$request = $request;
    }

    public function view(): View
    {
        $report = new InventoriesMovementExportController();
        
        return $report->movements_pdf($this->request->coin ?? 'dolares',$this->request->date_begin ?? 'todo',$this->request->date_end ?? 'todo',$this->request->type ?? 'todo',$this->request->id_inventories ?? 'todos',$this->request->id_account ?? 'todas');
    }

    

    public function setter($request){
        $this->request = $request;
     }

    
}

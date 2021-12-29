<?php

namespace App\Imports;

use App\Account;
use App\Product;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class ProductReadImport implements ToCollection,WithHeadingRow, SkipsOnError
{
    use Importable;
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function collection(Collection $rows)
    {
       
    }

    public function onError(\Throwable $e)
    {
        // Handle the exception how you'd like.
    }
}

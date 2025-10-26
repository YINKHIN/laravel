<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Import;

class RecalculateImportTotalsSeeder extends Seeder
{
    public function run()
    {
        $imports = Import::with('importDetails')->get();
        
        foreach($imports as $import) {
            $calculatedTotal = $import->importDetails->sum('amount');
            
            if ($import->total != $calculatedTotal) {
                echo "Import ID {$import->id}: Updating total from {$import->total} to {$calculatedTotal}\n";
                $import->update(['total' => $calculatedTotal]);
            } else {
                echo "Import ID {$import->id}: Total is correct ({$import->total})\n";
            }
        }
        
        echo "Recalculated totals for " . $imports->count() . " imports\n";
    }
}
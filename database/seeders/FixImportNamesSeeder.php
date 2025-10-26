<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Import;

class FixImportNamesSeeder extends Seeder
{
    public function run()
    {
        $imports = Import::all();
        
        foreach($imports as $import) {
            // Create proper import names based on supplier and date
            $supplier = $import->supplier;
            $date = $import->imp_date->format('M Y');
            $newName = "Import from {$supplier} - {$date}";
            
            $import->update(['full_name' => $newName]);
            
            echo "Updated Import ID {$import->id}: {$newName}\n";
        }
        
        echo "Fixed " . $imports->count() . " import names\n";
    }
}
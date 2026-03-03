<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BarangaySeeder extends Seeder
{
    public function run(): void
    {
        $barangays = [
            'ABELLA', 'ALLANG', 'AMTIC', // Add all your barangays here
            'BACONG', 'BAGUMBAYAN', 'BALANAC',
            'BALIGANG', 'BARAYONG', 'BASAG',
            'BATANG', 'BAY', 'BINANOWAN',
            'BINATAGAN', 'BOBONSURAN', 'BONGA',
            'BUSAC', 'BUSAY', 'CABARIAN',
            'CALZADA', 'CATBURAWAN', 'CAVASI',
            'CULLIAT', 'DUNAO', 'FRANCIA', 'GUILID', 'HERRERA',
            'LAYON', 'MACALIDONG', 'MAHABA', 'MALAMA', 'MAONON',
            'NABONTON', 'NASISI', 'OMA-OMA', 'PALAPAS', 'PANDAN',
            'PAULBA', 'PAULOG', 'PINAMANIQUIAN', 'PINIT', 'RANAO-RANAO',
            'SAN VICENTE', 'STA. CRUZ', 'TAGPO', 'TAMBO', 'TANDARURA',
            'TASTAS', 'TINAGO', 'TINAMPO', 'TIONGSON', 'TOMOLIN',
            'TUBURAN', 'TULA-TULA GRANDE', 'TULA-TULA PEQUEÑO', 'TUPAZ'
        ];

        foreach ($barangays as $barangay) {
            DB::table('barangays')->insert([
                'barangay_name' => $barangay,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

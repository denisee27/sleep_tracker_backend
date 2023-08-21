<?php

namespace App\Console\Commands;

use App\Models\PoSap;
use App\Models\PoSapDetail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\StorageAttributes;

class SapImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sap:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import SAP CSV Data';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        DB::beginTransaction();
        try {
            $imported = DB::table('sap_import')->select('file')->get()->pluck('file')->toArray();
            $files = Storage::listContents('.')
                ->filter(function (StorageAttributes $attributes) use ($imported) {
                    $tbl_name = explode('_', $attributes->path())[0];
                    return $attributes->isFile() &&
                        !Str::startsWith($attributes->path(), '.') &&
                        Str::endsWith($attributes->path(), '.csv') &&
                        in_array($tbl_name, ['MKPF', 'MSEG', 'EKKO', 'LFA1', 'ADRC', 'EKPO']) &&
                        !in_array($attributes->path(), $imported);
                });

            foreach ($files as $file) {
                $tbl_name = explode('_', $file->path())[0];
                $stream = Storage::readStream($file->path());
                $header = [];
                while (($row = fgetcsv($stream, null, "^")) !== false) {
                    if (!$header) {
                        $_header = $row;
                        $header = array_map(function ($v) {
                            return str_replace('/', '', $v);
                        }, $_header);
                    } else {
                        $item = array_combine($header, $row);
                        DB::table('sap_' . strtolower($tbl_name))->insert($item);
                    }
                }
                fclose($stream);
                DB::table('sap_import')->insert(['file' => $file->path()]);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->parseData();
    }

    /**
     * parseData
     *
     * @return void
     */
    private function parseData()
    {
        DB::beginTransaction();
        try {
            $mkpfs = DB::table('sap_mkpf')
                ->whereRaw('LENGTH(BUDAT) > 0')
                ->whereRaw('LENGTH(MBLNR) > 0')
                ->get();
            foreach ($mkpfs as $mkpf) {
                $mseg = DB::table('sap_mseg')
                    ->where('MBLNR', $mkpf->MBLNR)
                    ->whereRaw('LENGTH(EBELN) > 0')
                    ->whereRaw("SUBSTRING(EBELN, 1, 3) IN ('400','401','402')")
                    ->first();
                if (!$mseg) {
                    continue;
                }
                $po_number = trim($mseg->EBELN);
                $hasPO = PoSap::where('number', $po_number)->first();
                if ($hasPO) {
                    continue;
                }

                $ekko = DB::table('sap_ekko')->where('EBELN', $po_number)->first();
                if (!$ekko) {
                    continue;
                }

                $po_date = Carbon::createFromFormat('Ymd', $ekko->BEDAT)->format('Y-m-d');
                $supplier = null;
                $lfa1 = DB::table('sap_lfa1')->where('LIFNR', $ekko->LIFNR)->first();
                if ($lfa1) {
                    $adrc = DB::table('sap_adrc')->where('ADDRNUMBER', $lfa1->ADRNR)->first();
                    $supplier = $adrc->NAME1 ?? null;
                }
                $incoterms = trim($ekko->INCO1 . ' ' . $ekko->INCO2);

                $savePO = new PoSap();
                $savePO->number = $po_number;
                $savePO->po_date = $po_date;
                $savePO->incoterms = $incoterms;
                $savePO->supplier = $supplier;
                $savePO->save();

                $ekpo = DB::table('sap_ekpo')
                    ->where('EBELN', $ekko->EBELN)
                    ->get();
                foreach ($ekpo as $e) {
                    $number = substr($e->MATNR, 8);
                    $_price = str_replace('.', '', $e->NETPR);
                    $price = str_replace(',', '.', $_price);
                    $PoDetail = new PoSapDetail();
                    $PoDetail->po_sap_id = $savePO->id;
                    $PoDetail->number = $number;
                    $PoDetail->name = $e->TXZ01;
                    $PoDetail->qty = $e->MENGE;
                    $PoDetail->uom = $e->MEINS;
                    $PoDetail->currency = $ekko->WAERS;
                    $PoDetail->price = $price;
                    $PoDetail->save();
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        DB::table('sap_adrc')->truncate();
        DB::table('sap_ekko')->truncate();
        DB::table('sap_ekpo')->truncate();
        DB::table('sap_lfa1')->truncate();
        DB::table('sap_mkpf')->truncate();
        DB::table('sap_mseg')->truncate();
    }
}

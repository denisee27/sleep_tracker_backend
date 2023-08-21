<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Material;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImporterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:go';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $json = '[
            {
                "number": 1000000233,
                "name": "FO - Aerial 24 Core G 652 D Single Mode",
                "category": "Kabel FO",
                "uom": "Meters",
                "description": "Kabel Optik Kapasitas 24 Core yang digunakan udara",
                "minimum_stock": {
                    "main": 10000,
                    "transit": 8000,
                    "lastmile": 1000
                }
            },
            {
                "number": 1000000108,
                "name": "FO - Aerial 48 Core G 652 D Single Mode",
                "category": "Kabel FO",
                "uom": "Meters",
                "description": "Kabel Optik Kapasitas 48 Core yang digunakan udara",
                "minimum_stock": {
                    "main": 10000,
                    "transit": 8000,
                    "lastmile": 1000
                }
            },
            {
                "number": 1000000110,
                "name": "FO - Aerial 96 Core G 652 D Single Mode",
                "category": "Kabel FO",
                "uom": "Meters",
                "description": "Kabel Optik Kapasitas 96 Core yang digunakan udara",
                "minimum_stock": {
                    "main": 10000,
                    "transit": 8000,
                    "lastmile": 1000
                }
            },
            {
                "number": 1000000329,
                "name": "FO - Aerial 120 Core G 652 D",
                "category": "Kabel FO",
                "uom": "Meters",
                "description": "Kabel Optik Kapasitas 120 Core yang digunakan udara",
                "minimum_stock": {
                    "main": 10000,
                    "transit": 8000,
                    "lastmile": 1000
                }
            },
            {
                "number": 1000000107,
                "name": "FO - ADSS 12 Core G 652 D",
                "category": "Kabel FO",
                "uom": "Meters",
                "description": "Kabel Optik Kapasitas 12 Core yang digunakan di tanah & udara",
                "minimum_stock": {
                    "main": 10000,
                    "transit": 8000,
                    "lastmile": 1000
                }
            },
            {
                "number": 1000000792,
                "name": "FO - ADSS 24 Core",
                "category": "Kabel FO",
                "uom": "Meters",
                "description": "Kabel Optik Kapasitas 24 Core yang digunakan di tanah & udara",
                "minimum_stock": {
                    "main": 10000,
                    "transit": 8000,
                    "lastmile": 1000
                }
            },
            {
                "number": 1000000850,
                "name": "FO - ADSS 72 Core",
                "category": "Kabel FO",
                "uom": "Meters",
                "description": "Kabel Optik Kapasitas 72 Core yang digunakan di tanah & udara",
                "minimum_stock": {
                    "main": 10000,
                    "transit": 8000,
                    "lastmile": 1000
                }
            },
            {
                "number": 1000000695,
                "name": "FO - ADSS 144 Core",
                "category": "Kabel FO",
                "uom": "Meters",
                "description": "Kabel Optik Kapasitas 144 Core yang digunakan di tanah & udara",
                "minimum_stock": {
                    "main": 10000,
                    "transit": 8000,
                    "lastmile": 1000
                }
            },
            {
                "number": 1000000797,
                "name": "FO - ADSS 288 Core",
                "category": "Kabel FO",
                "uom": "Meters",
                "description": "Kabel Optik Kapasitas 288 Core yang digunakan di tanah & udara",
                "minimum_stock": {
                    "main": 10000,
                    "transit": 8000,
                    "lastmile": 1000
                }
            },
            {
                "number": 1000000113,
                "name": "FO - Duct 2 Core G 652 D Single Mode",
                "category": "Kabel FO",
                "uom": "Meters",
                "description": "Kabel Optik Kapasitas 2 Core yang digunakan di tanah",
                "minimum_stock": {
                    "main": 10000,
                    "transit": 8000,
                    "lastmile": 1000
                }
            },
            {
                "number": 1000000115,
                "name": "FO - Duct 24 Core G 652 D",
                "category": "Kabel FO",
                "uom": "Meters",
                "description": "Kabel Optik Kapasitas 24 Core yang digunakan di tanah",
                "minimum_stock": {
                    "main": 10000,
                    "transit": 8000,
                    "lastmile": 1000
                }
            },
            {
                "number": 1000000116,
                "name": "FO - Duct 24 Core G 652 D Single Mode",
                "category": "Kabel FO",
                "uom": "Meters",
                "description": "Kabel Optik Kapasitas 24 Core yang digunakan di tanah",
                "minimum_stock": {
                    "main": 10000,
                    "transit": 8000,
                    "lastmile": 1000
                }
            },
            {
                "number": 1000001158,
                "name": "FO - Duct 24 Core G 654 B",
                "category": "Kabel FO",
                "uom": "Meters",
                "description": "Kabel Optik Kapasitas 24 Core yang digunakan di tanah",
                "minimum_stock": {
                    "main": 10000,
                    "transit": 8000,
                    "lastmile": 1000
                }
            },
            {
                "number": 1000000117,
                "name": "FO - Duct 48 Core G 652 D",
                "category": "Kabel FO",
                "uom": "Meters",
                "description": "Kabel Optik Kapasitas 48 Core yang digunakan di tanah",
                "minimum_stock": {
                    "main": 10000,
                    "transit": 8000,
                    "lastmile": 1000
                }
            },
            {
                "number": 1000000849,
                "name": "FO - Duct 72 Core",
                "category": "Kabel FO",
                "uom": "Meters",
                "description": "Kabel Optik Kapasitas 72 Core yang digunakan di tanah",
                "minimum_stock": {
                    "main": 10000,
                    "transit": 8000,
                    "lastmile": 1000
                }
            },
            {
                "number": 1000000120,
                "name": "FO - Duct 96 Core G 652 C",
                "category": "Kabel FO",
                "uom": "Meters",
                "description": "Kabel Optik Kapasitas 96 Core yang digunakan di tanah",
                "minimum_stock": {
                    "main": 10000,
                    "transit": 8000,
                    "lastmile": 1000
                }
            },
            {
                "number": 1000000121,
                "name": "FO - Duct 96 Core G 652 D",
                "category": "Kabel FO",
                "uom": "Meters",
                "description": "Kabel Optik Kapasitas 96 Core yang digunakan di tanah",
                "minimum_stock": {
                    "main": 10000,
                    "transit": 8000,
                    "lastmile": 1000
                }
            },
            {
                "number": 1000000123,
                "name": "FO - Duct 96 Core G 652 D Single Mode",
                "category": "Kabel FO",
                "uom": "Meters",
                "description": "Kabel Optik Kapasitas 96 Core yang digunakan di tanah",
                "minimum_stock": {
                    "main": 10000,
                    "transit": 8000,
                    "lastmile": 1000
                }
            },
            {
                "number": 1000000851,
                "name": "FO - Duct 216 Core",
                "category": "Kabel FO",
                "uom": "Meters",
                "description": "Kabel Optik Kapasitas 216 Core yang digunakan di tanah",
                "minimum_stock": {
                    "main": 10000,
                    "transit": 8000,
                    "lastmile": 1000
                }
            },
            {
                "number": 1000000968,
                "name": "FO - Duct 288 Core G652 D",
                "category": "Kabel FO",
                "uom": "Meters",
                "description": "Kabel Optik Kapasitas 288 Core yang digunakan di tanah",
                "minimum_stock": {
                    "main": 10000,
                    "transit": 8000,
                    "lastmile": 1000
                }
            },
            {
                "number": 1000000896,
                "name": "FO - Duct 288 Core G652 D Single Mode",
                "category": "Kabel FO",
                "uom": "Meters",
                "description": "Kabel Optik Kapasitas 288 Core yang digunakan di tanah",
                "minimum_stock": {
                    "main": 10000,
                    "transit": 8000,
                    "lastmile": 1000
                }
            },
            {
                "number": 1000001131,
                "name": "FO - Hybrid 24C (G654D 12C + G654C 12C Sumitomo)",
                "category": "Kabel FO",
                "uom": "Meters",
                "description": "Kabel Optik Kapasitas 24 Core yang dalam 1 kabel terdapat 2 type fiber/core",
                "minimum_stock": {
                    "main": 10000,
                    "transit": 8000,
                    "lastmile": 1000
                }
            },
            {
                "number": 1000000822,
                "name": "FO - Hybrid 96C (G652D 60C + G654C 36C ULL Corning)",
                "category": "Kabel FO",
                "uom": "Meters",
                "description": "Kabel Optik Kapasitas 96 Core yang dalam 1 kabel terdapat 2 type fiber/core",
                "minimum_stock": {
                    "main": 10000,
                    "transit": 8000,
                    "lastmile": 1000
                }
            },
            {
                "number": 1000001009,
                "name": "FO - Hybrid 96C (G652D 72C + G65CC 24C Corning)",
                "category": "Kabel FO",
                "uom": "Meters",
                "description": "Kabel Optik Kapasitas 96 Core yang dalam 1 kabel terdapat 2 type fiber/core",
                "minimum_stock": {
                    "main": 10000,
                    "transit": 8000,
                    "lastmile": 1000
                }
            },
            {
                "number": 1000000709,
                "name": "Joint Closure 12 Core",
                "category": "Joint Closure",
                "uom": "Pcs",
                "description": "Sebuah wadah meletakkan core hasil sambungan dari kabel fiber optic kapasitas 12 Core",
                "minimum_stock": {
                    "main": 300,
                    "transit": 120,
                    "lastmile": 10
                }
            },
            {
                "number": 1000000032,
                "name": "Joint Closure 24 Core Polos",
                "category": "Joint Closure",
                "uom": "Pcs",
                "description": "Sebuah wadah meletakkan core hasil sambungan dari kabel fiber optic kapasitas 24 Core",
                "minimum_stock": {
                    "main": 300,
                    "transit": 120,
                    "lastmile": 10
                }
            },
            {
                "number": 1000000033,
                "name": "Joint Closure 24 Core 3M",
                "category": "Joint Closure",
                "uom": "Pcs",
                "description": "Sebuah wadah meletakkan core hasil sambungan dari kabel fiber optic kapasitas 24 Core",
                "minimum_stock": {
                    "main": 300,
                    "transit": 120,
                    "lastmile": 10
                }
            },
            {
                "number": 1000000034,
                "name": "Joint Closure 24 Core China",
                "category": "Joint Closure",
                "uom": "Pcs",
                "description": "Sebuah wadah meletakkan core hasil sambungan dari kabel fiber optic kapasitas 24 Core",
                "minimum_stock": {
                    "main": 300,
                    "transit": 120,
                    "lastmile": 10
                }
            },
            {
                "number": 1000000035,
                "name": "Joint Closure 24 Core Nwc",
                "category": "Joint Closure",
                "uom": "Pcs",
                "description": "Sebuah wadah meletakkan core hasil sambungan dari kabel fiber optic kapasitas 24 Core",
                "minimum_stock": {
                    "main": 300,
                    "transit": 120,
                    "lastmile": 10
                }
            },
            {
                "number": 1000000044,
                "name": "Joint Closure 72 Core Polos",
                "category": "Joint Closure",
                "uom": "Pcs",
                "description": "Sebuah wadah meletakkan core hasil sambungan dari kabel fiber optic kapasitas 72 Core",
                "minimum_stock": {
                    "main": 300,
                    "transit": 120,
                    "lastmile": 10
                }
            },
            {
                "number": 1000000046,
                "name": "Joint Closure 72 Core China",
                "category": "Joint Closure",
                "uom": "Pcs",
                "description": "Sebuah wadah meletakkan core hasil sambungan dari kabel fiber optic kapasitas 72 Core",
                "minimum_stock": {
                    "main": 300,
                    "transit": 120,
                    "lastmile": 10
                }
            },
            {
                "number": 1000000191,
                "name": "Joint Closure 72 Core FujiKUra",
                "category": "Joint Closure",
                "uom": "Pcs",
                "description": "Sebuah wadah meletakkan core hasil sambungan dari kabel fiber optic kapasitas 72 Core",
                "minimum_stock": {
                    "main": 300,
                    "transit": 120,
                    "lastmile": 10
                }
            },
            {
                "number": 1000000050,
                "name": "Joint Closure 96 Core Polos",
                "category": "Joint Closure",
                "uom": "Pcs",
                "description": "Sebuah wadah meletakkan core hasil sambungan dari kabel fiber optic kapasitas 96 Core",
                "minimum_stock": {
                    "main": 300,
                    "transit": 120,
                    "lastmile": 10
                }
            },
            {
                "number": 1000000053,
                "name": "Joint Closure 96 Core Nwc",
                "category": "Joint Closure",
                "uom": "Pcs",
                "description": "Sebuah wadah meletakkan core hasil sambungan dari kabel fiber optic kapasitas 96 Core",
                "minimum_stock": {
                    "main": 300,
                    "transit": 120,
                    "lastmile": 10
                }
            },
            {
                "number": 1000000056,
                "name": "Joint Closure 144 Polos",
                "category": "Joint Closure",
                "uom": "Pcs",
                "description": "Sebuah wadah meletakkan core hasil sambungan dari kabel fiber optic kapasitas 144 Core",
                "minimum_stock": {
                    "main": 300,
                    "transit": 120,
                    "lastmile": 10
                }
            },
            {
                "number": 1000000057,
                "name": "Joint Closure 144 Core 3M",
                "category": "Joint Closure",
                "uom": "Pcs",
                "description": "Sebuah wadah meletakkan core hasil sambungan dari kabel fiber optic kapasitas 144 Core",
                "minimum_stock": {
                    "main": 300,
                    "transit": 120,
                    "lastmile": 10
                }
            },
            {
                "number": 1000000059,
                "name": "Joint Closure 144 Core Nwc",
                "category": "Joint Closure",
                "uom": "Pcs",
                "description": "Sebuah wadah meletakkan core hasil sambungan dari kabel fiber optic kapasitas 144 Core",
                "minimum_stock": {
                    "main": 300,
                    "transit": 120,
                    "lastmile": 10
                }
            },
            {
                "number": 1000001091,
                "name": "Joint Closure 120 Core",
                "category": "Joint Closure",
                "uom": "Pcs",
                "description": "Sebuah wadah meletakkan core hasil sambungan dari kabel fiber optic kapasitas 120 Core",
                "minimum_stock": {
                    "main": 300,
                    "transit": 120,
                    "lastmile": 10
                }
            },
            {
                "number": 1000000318,
                "name": "Joint Closure 288 Core Nwc",
                "category": "Joint Closure",
                "uom": "Pcs",
                "description": "Sebuah wadah meletakkan core hasil sambungan dari kabel fiber optic kapasitas 288 Core",
                "minimum_stock": {
                    "main": 300,
                    "transit": 120,
                    "lastmile": 10
                }
            },
            {
                "number": 1000000798,
                "name": "Joint Closure 288 Core Polos",
                "category": "Joint Closure",
                "uom": "Pcs",
                "description": "Sebuah wadah meletakkan core hasil sambungan dari kabel fiber optic kapasitas 288 Core",
                "minimum_stock": {
                    "main": 300,
                    "transit": 120,
                    "lastmile": 10
                }
            },
            {
                "number": 1000000161,
                "name": "Dome Closure 96 Core - KU",
                "category": "Joint Closure",
                "uom": "Pcs",
                "description": "Sebuah wadah khusus KU meletakkan core hasil sambungan dari kabel fiber optic kapasitas 96 Core",
                "minimum_stock": {
                    "main": 300,
                    "transit": 120,
                    "lastmile": 10
                }
            },
            {
                "number": 1000001038,
                "name": "Dome Closure 96 Core",
                "category": "Joint Closure",
                "uom": "Pcs",
                "description": "Sebuah wadah khusus KU meletakkan core hasil sambungan dari kabel fiber optic kapasitas 96 Core",
                "minimum_stock": {
                    "main": 300,
                    "transit": 120,
                    "lastmile": 10
                }
            },
            {
                "number": 1000000165,
                "name": "Beach Joint Box 24 Core G654B Nexans",
                "category": "Joint Closure",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000171,
                "name": "Wtc-1 Splice Closure 24 Core",
                "category": "Joint Closure",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000716,
                "name": "HDPE Pe100 Pn16 Sdr 11",
                "category": "Subduct HDPE",
                "uom": "Meters",
                "description": "Pipa ini biasa digunakan sebagai pelindung kabel yang ditanam didalam tanah",
                "minimum_stock": {
                    "main": 5000,
                    "transit": 1600,
                    "lastmile": 200
                }
            },
            {
                "number": 1000000095,
                "name": "HDPE 40/34 Biru Polos",
                "category": "Subduct HDPE",
                "uom": "Meters",
                "description": "Pipa ini biasa digunakan sebagai pelindung kabel yang ditanam didalam tanah",
                "minimum_stock": {
                    "main": 5000,
                    "transit": 1600,
                    "lastmile": 200
                }
            },
            {
                "number": 1000000097,
                "name": "HDPE 40/34 Biru Strip Orange",
                "category": "Subduct HDPE",
                "uom": "Meters",
                "description": "Pipa ini biasa digunakan sebagai pelindung kabel yang ditanam didalam tanah",
                "minimum_stock": {
                    "main": 5000,
                    "transit": 1600,
                    "lastmile": 200
                }
            },
            {
                "number": 1000000971,
                "name": "HDPE 40/34 Kuning Strip Biru",
                "category": "Subduct HDPE",
                "uom": "Meters",
                "description": "Pipa ini biasa digunakan sebagai pelindung kabel yang ditanam didalam tanah",
                "minimum_stock": {
                    "main": 5000,
                    "transit": 1600,
                    "lastmile": 200
                }
            },
            {
                "number": 1000000824,
                "name": "HDPE 40/34 Pink Strip Hitam",
                "category": "Subduct HDPE",
                "uom": "Meters",
                "description": "Pipa ini biasa digunakan sebagai pelindung kabel yang ditanam didalam tanah",
                "minimum_stock": {
                    "main": 5000,
                    "transit": 1600,
                    "lastmile": 200
                }
            },
            {
                "number": 1000000812,
                "name": "HDPE 40/32",
                "category": "Subduct HDPE",
                "uom": "Meters",
                "description": "Pipa ini biasa digunakan sebagai pelindung kabel yang ditanam didalam tanah",
                "minimum_stock": {
                    "main": 5000,
                    "transit": 1600,
                    "lastmile": 200
                }
            },
            {
                "number": 1000000094,
                "name": "HDPE 40/32 Hijau Strip Hitam",
                "category": "Subduct HDPE",
                "uom": "Meters",
                "description": "Pipa ini biasa digunakan sebagai pelindung kabel yang ditanam didalam tanah",
                "minimum_stock": {
                    "main": 5000,
                    "transit": 1600,
                    "lastmile": 200
                }
            },
            {
                "number": 1000000223,
                "name": "HDPE 33/27 Merah Strip Biru",
                "category": "Subduct HDPE",
                "uom": "Meters",
                "description": "Pipa ini biasa digunakan sebagai pelindung kabel yang ditanam didalam tanah",
                "minimum_stock": {
                    "main": 5000,
                    "transit": 1600,
                    "lastmile": 200
                }
            },
            {
                "number": 1000000577,
                "name": "HDPE 33/27 Biru Strip Merah",
                "category": "Subduct HDPE",
                "uom": "Meters",
                "description": "Pipa ini biasa digunakan sebagai pelindung kabel yang ditanam didalam tanah",
                "minimum_stock": {
                    "main": 5000,
                    "transit": 1600,
                    "lastmile": 200
                }
            },
            {
                "number": 1000000823,
                "name": "HDPE 33/27 Biru Strip Orange",
                "category": "Subduct HDPE",
                "uom": "Meters",
                "description": "Pipa ini biasa digunakan sebagai pelindung kabel yang ditanam didalam tanah",
                "minimum_stock": {
                    "main": 5000,
                    "transit": 1600,
                    "lastmile": 200
                }
            },
            {
                "number": 1000000913,
                "name": "HDPE 33/27 Merah Strip Hijau",
                "category": "Subduct HDPE",
                "uom": "Meters",
                "description": "Pipa ini biasa digunakan sebagai pelindung kabel yang ditanam didalam tanah",
                "minimum_stock": {
                    "main": 5000,
                    "transit": 1600,
                    "lastmile": 200
                }
            },
            {
                "number": 1000000100,
                "name": "HDPE 32/28",
                "category": "Subduct HDPE",
                "uom": "Meters",
                "description": "Pipa ini biasa digunakan sebagai pelindung kabel yang ditanam didalam tanah",
                "minimum_stock": {
                    "main": 5000,
                    "transit": 1600,
                    "lastmile": 200
                }
            },
            {
                "number": 1000000762,
                "name": "HDPE 30/25",
                "category": "Subduct HDPE",
                "uom": "Meters",
                "description": "Pipa ini biasa digunakan sebagai pelindung kabel yang ditanam didalam tanah",
                "minimum_stock": {
                    "main": 5000,
                    "transit": 1600,
                    "lastmile": 200
                }
            },
            {
                "number": 1000000159,
                "name": "Warning Tape Metalized Orange",
                "category": "Pole & Accessories",
                "uom": "Meters",
                "description": "Untuk menghalangi, membatasi, dan mengontrol area sekitar selama periode tertentu",
                "minimum_stock": ""
            },
            {
                "number": 1000000319,
                "name": "Label Kabel Duck",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000167,
                "name": "Label Handhole",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000168,
                "name": "Label BMH",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000197,
                "name": "Fitting Dead End",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Untuk menjepit kabel udara fiber optik",
                "minimum_stock": ""
            },
            {
                "number": 1000000198,
                "name": "Fitting Suspension",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Untuk menjepit kabel udara fiber optik",
                "minimum_stock": ""
            },
            {
                "number": 1000000234,
                "name": "Suspension Bracket",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Untuk menjepit kabel udara fiber optik",
                "minimum_stock": ""
            },
            {
                "number": 1000001159,
                "name": "Klem Buaya 25-50",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Untuk menjepit kabel udara fiber optik",
                "minimum_stock": {
                    "main": 500,
                    "transit": 80,
                    "lastmile": 20
                }
            },
            {
                "number": 1000001160,
                "name": "Klem Buaya 50-70",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Untuk menjepit kabel udara fiber optik",
                "minimum_stock": {
                    "main": 500,
                    "transit": 80,
                    "lastmile": 20
                }
            },
            {
                "number": 1000001161,
                "name": "Klem Buaya 70-95",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Untuk menjepit kabel udara fiber optik",
                "minimum_stock": {
                    "main": 500,
                    "transit": 80,
                    "lastmile": 20
                }
            },
            {
                "number": 1000000235,
                "name": "Dead End Braket",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Untuk menjepit kabel udara fiber optik",
                "minimum_stock": {
                    "main": 500,
                    "transit": 80,
                    "lastmile": 20
                }
            },
            {
                "number": 1000000575,
                "name": "Hanger Cable",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Untuk merapihkan sisa kabel fiber optic",
                "minimum_stock": {
                    "main": 250,
                    "transit": 30,
                    "lastmile": 5
                }
            },
            {
                "number": 1000000320,
                "name": "Label Kabel Tiang",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000331,
                "name": "Stainless Steel Band",
                "category": "Pole & Accessories",
                "uom": "Roll",
                "description": "Untuk pengikat klem diatas tiang, material dari baja anti karat dengan kandungan 12% chromium",
                "minimum_stock": {
                    "main": 50,
                    "transit": 5,
                    "lastmile": 1
                }
            },
            {
                "number": 1000000653,
                "name": "Steel Straping Band",
                "category": "Pole & Accessories",
                "uom": "Roll",
                "description": "Untuk pengikat klem diatas tiang, material dari baja biasa",
                "minimum_stock": {
                    "main": 50,
                    "transit": 5,
                    "lastmile": 1
                }
            },
            {
                "number": 1000000169,
                "name": "Band It Strapping Band",
                "category": "Pole & Accessories",
                "uom": "Roll",
                "description": "Untuk pengikat klem diatas tiang, material bukan dari Baja",
                "minimum_stock": {
                    "main": 50,
                    "transit": 5,
                    "lastmile": 1
                }
            },
            {
                "number": 1000000082,
                "name": "Pipe Galvanis 2 Inc 1,7 Mm X 6 M",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000085,
                "name": "Pipe Galvanis 2 Inc 3,2 Mm Medium",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000087,
                "name": "Pipe Galvanis 2 Inc 3,9 Mm X 6 M",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000576,
                "name": "Pipe Galvanis 3 Inc 2,3 Mm",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000102,
                "name": "Pipe Tiang - Listrik 9 M Beton",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Sarana penunjang untuk menempatkan jaringan kabel fiber optik",
                "minimum_stock": {
                    "main": 40,
                    "transit": 20,
                    "lastmile": 0
                }
            },
            {
                "number": 1000000103,
                "name": "Pipe Tiang - Telpon 7 M Besi",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Sarana penunjang untuk menempatkan jaringan kabel fiber optik",
                "minimum_stock": {
                    "main": 40,
                    "transit": 20,
                    "lastmile": 0
                }
            },
            {
                "number": 1000000104,
                "name": "Pipe Tiang - Telpon 9 M Besi",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Sarana penunjang untuk menempatkan jaringan kabel fiber optik",
                "minimum_stock": {
                    "main": 40,
                    "transit": 20,
                    "lastmile": 0
                }
            },
            {
                "number": 1000000192,
                "name": "Pipe Tiang - Telpon 12 M Besi",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Sarana penunjang untuk menempatkan jaringan kabel fiber optik",
                "minimum_stock": {
                    "main": 40,
                    "transit": 20,
                    "lastmile": 0
                }
            },
            {
                "number": 1000001133,
                "name": "Tiang Besi 7 M (4+3) Spek Moratel/Telk",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Sarana penunjang untuk menempatkan jaringan kabel fiber optik",
                "minimum_stock": {
                    "main": 40,
                    "transit": 20,
                    "lastmile": 0
                }
            },
            {
                "number": 1000001134,
                "name": "Tiang Besi 7 M (6+1) Spek Fiberstar",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Sarana penunjang untuk menempatkan jaringan kabel fiber optik",
                "minimum_stock": {
                    "main": 40,
                    "transit": 20,
                    "lastmile": 0
                }
            },
            {
                "number": 1000001136,
                "name": "Tiang Besi 9 M (6+3) Spek Fiberstar",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Sarana penunjang untuk menempatkan jaringan kabel fiber optik",
                "minimum_stock": {
                    "main": 40,
                    "transit": 20,
                    "lastmile": 0
                }
            },
            {
                "number": 1000001135,
                "name": "Tiang Besi 9 M (6+3) Spek Moratel/Telk",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Sarana penunjang untuk menempatkan jaringan kabel fiber optik",
                "minimum_stock": {
                    "main": 40,
                    "transit": 20,
                    "lastmile": 0
                }
            },
            {
                "number": 1000000311,
                "name": "Tiang Beton 7 Meter",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Sarana penunjang untuk menempatkan jaringan kabel fiber optik",
                "minimum_stock": {
                    "main": 40,
                    "transit": 20,
                    "lastmile": 0
                }
            },
            {
                "number": 1000000313,
                "name": "Tiang Beton 9 Meter",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Sarana penunjang untuk menempatkan jaringan kabel fiber optik",
                "minimum_stock": {
                    "main": 40,
                    "transit": 20,
                    "lastmile": 0
                }
            },
            {
                "number": 1000000789,
                "name": "Tiang Beton 11 Meter",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Sarana penunjang untuk menempatkan jaringan kabel fiber optik",
                "minimum_stock": {
                    "main": 40,
                    "transit": 20,
                    "lastmile": 0
                }
            },
            {
                "number": 1000000314,
                "name": "Tiang Beton 12 Meter",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Sarana penunjang untuk menempatkan jaringan kabel fiber optik",
                "minimum_stock": {
                    "main": 40,
                    "transit": 20,
                    "lastmile": 0
                }
            },
            {
                "number": 1000000317,
                "name": "Tiang Beton 13 Meter",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Sarana penunjang untuk menempatkan jaringan kabel fiber optik",
                "minimum_stock": {
                    "main": 40,
                    "transit": 20,
                    "lastmile": 0
                }
            },
            {
                "number": 1000000315,
                "name": "Tiang Beton 14 Meter",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Sarana penunjang untuk menempatkan jaringan kabel fiber optik",
                "minimum_stock": {
                    "main": 40,
                    "transit": 20,
                    "lastmile": 0
                }
            },
            {
                "number": 1000000089,
                "name": "Galvanis 100Mm Th. 3,6Mm-Luwuk (130M)",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000091,
                "name": "Galvanis Steel Pipe 4\" 3,65 Thickness",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000172,
                "name": "Bearer Cable Galvanized 600Mm W/Support",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000173,
                "name": "Ladder Galvanized 2,576 M X 0,60 M",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000228,
                "name": "Riser Pipe Galvanis 2\" Tebal 2.8Mm",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000174,
                "name": "Pulling Iron",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000175,
                "name": "Duct Seal Foam",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000010,
                "name": "OTB 24 Core 3M",
                "category": "Pole & Accessories",
                "uom": "Unit",
                "description": "Menghubungkan kabel fiber optik indoor maupun outdoor dan konektor",
                "minimum_stock": {
                    "main": 25,
                    "transit": 5,
                    "lastmile": 0
                }
            },
            {
                "number": 1000000011,
                "name": "OTB 24 Core China",
                "category": "Pole & Accessories",
                "uom": "Unit",
                "description": "Menghubungkan kabel fiber optik indoor maupun outdoor dan konektor",
                "minimum_stock": {
                    "main": 25,
                    "transit": 5,
                    "lastmile": 0
                }
            },
            {
                "number": 1000000013,
                "name": "OTB 24 Core Nwc",
                "category": "Pole & Accessories",
                "uom": "Unit",
                "description": "Menghubungkan kabel fiber optik indoor maupun outdoor dan konektor",
                "minimum_stock": {
                    "main": 25,
                    "transit": 5,
                    "lastmile": 0
                }
            },
            {
                "number": 1000000016,
                "name": "OTB 48 Core China",
                "category": "Pole & Accessories",
                "uom": "Unit",
                "description": "Menghubungkan kabel fiber optik indoor maupun outdoor dan konektor",
                "minimum_stock": {
                    "main": 25,
                    "transit": 5,
                    "lastmile": 0
                }
            },
            {
                "number": 1000000018,
                "name": "OTB 48 Core Nwc",
                "category": "Pole & Accessories",
                "uom": "Unit",
                "description": "Menghubungkan kabel fiber optik indoor maupun outdoor dan konektor",
                "minimum_stock": {
                    "main": 25,
                    "transit": 5,
                    "lastmile": 0
                }
            },
            {
                "number": 1000000025,
                "name": "OTB 96 Core 3M",
                "category": "Pole & Accessories",
                "uom": "Unit",
                "description": "Menghubungkan kabel fiber optik indoor maupun outdoor dan konektor",
                "minimum_stock": {
                    "main": 25,
                    "transit": 5,
                    "lastmile": 0
                }
            },
            {
                "number": 1000000026,
                "name": "OTB 96 Core China",
                "category": "Pole & Accessories",
                "uom": "Unit",
                "description": "Menghubungkan kabel fiber optik indoor maupun outdoor dan konektor",
                "minimum_stock": {
                    "main": 25,
                    "transit": 5,
                    "lastmile": 0
                }
            },
            {
                "number": 1000000028,
                "name": "OTB 96 Core Nwc",
                "category": "Pole & Accessories",
                "uom": "Unit",
                "description": "Menghubungkan kabel fiber optik indoor maupun outdoor dan konektor",
                "minimum_stock": {
                    "main": 25,
                    "transit": 5,
                    "lastmile": 0
                }
            },
            {
                "number": 1000001036,
                "name": "OTB 96 Core Dan Pigtail 96 Core",
                "category": "Pole & Accessories",
                "uom": "Unit",
                "description": "Menghubungkan kabel fiber optik indoor maupun outdoor dan konektor",
                "minimum_stock": {
                    "main": 25,
                    "transit": 5,
                    "lastmile": 0
                }
            },
            {
                "number": 1000000006,
                "name": "OTB 144 Core China",
                "category": "Pole & Accessories",
                "uom": "Unit",
                "description": "Menghubungkan kabel fiber optik indoor maupun outdoor dan konektor",
                "minimum_stock": {
                    "main": 25,
                    "transit": 5,
                    "lastmile": 0
                }
            },
            {
                "number": 1000000008,
                "name": "OTB 144 Core Nwc",
                "category": "Pole & Accessories",
                "uom": "Unit",
                "description": "Menghubungkan kabel fiber optik indoor maupun outdoor dan konektor",
                "minimum_stock": {
                    "main": 25,
                    "transit": 5,
                    "lastmile": 0
                }
            },
            {
                "number": 1000001127,
                "name": "OTB Paz Sc/Upc Singlemode",
                "category": "Pole & Accessories",
                "uom": "Unit",
                "description": "Menghubungkan kabel fiber optik indoor maupun outdoor dan konektor",
                "minimum_stock": {
                    "main": 25,
                    "transit": 5,
                    "lastmile": 0
                }
            },
            {
                "number": 1000000606,
                "name": "ODC Kap. 288 Cores",
                "category": "Pole & Accessories",
                "uom": "Unit",
                "description": "",
                "minimum_stock": null
            },
            {
                "number": 1000001037,
                "name": "ODP 24 Core Dan Pigtail 24 Core",
                "category": "Pole & Accessories",
                "uom": "Unit",
                "description": "Tempat instalasi sambungan jaringan optik single-mode terutama untuk menghubungkan kabel fiberoptik distribusi dan kabel drop",
                "minimum_stock": null
            },
            {
                "number": 1000000077,
                "name": "Pigtail 2 M",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Sebagai pengakhir dari fiber optik melalui penyambungan fusi atau penyambungan mekanis",
                "minimum_stock": {
                    "main": 1400,
                    "transit": 200,
                    "lastmile": 48
                }
            },
            {
                "number": 1000000731,
                "name": "Pigtail Sc - Apc 1.5 M",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Sebagai pengakhir dari fiber optik melalui penyambungan fusi atau penyambungan mekanis",
                "minimum_stock": {
                    "main": 1400,
                    "transit": 200,
                    "lastmile": 48
                }
            },
            {
                "number": 1000000732,
                "name": "Pigtail Sc - Apc 3 M",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Sebagai pengakhir dari fiber optik melalui penyambungan fusi atau penyambungan mekanis",
                "minimum_stock": {
                    "main": 1400,
                    "transit": 200,
                    "lastmile": 48
                }
            },
            {
                "number": 1000000889,
                "name": "Pigtail Sc/Upc",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Sebagai pengakhir dari fiber optik melalui penyambungan fusi atau penyambungan mekanis",
                "minimum_stock": {
                    "main": 1400,
                    "transit": 200,
                    "lastmile": 48
                }
            },
            {
                "number": 1000000879,
                "name": "Pigtail Fc/Upc",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Sebagai pengakhir dari fiber optik melalui penyambungan fusi atau penyambungan mekanis",
                "minimum_stock": {
                    "main": 1400,
                    "transit": 200,
                    "lastmile": 48
                }
            },
            {
                "number": 1000000190,
                "name": "Pigtail Type G655 C /Fc-Pc/3 M",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Sebagai pengakhir dari fiber optik melalui penyambungan fusi atau penyambungan mekanis",
                "minimum_stock": {
                    "main": 1400,
                    "transit": 200,
                    "lastmile": 48
                }
            },
            {
                "number": 1000000774,
                "name": "Patchcord Fc/Upc - Fc/Upc Sx,Sm 5 Meter",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Untuk menghubungkan perangkat pasif ke aktif",
                "minimum_stock": {
                    "main": 450,
                    "transit": 25,
                    "lastmile": 6
                }
            },
            {
                "number": 1000000842,
                "name": "Patchcord Fc/Upc - Sc/Apc Sx,Sm 5 Meter",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Untuk menghubungkan perangkat pasif ke aktif",
                "minimum_stock": {
                    "main": 450,
                    "transit": 25,
                    "lastmile": 6
                }
            },
            {
                "number": 1000000801,
                "name": "Patchcord Fc/Upc - Sc/Apc Dx,Sm 5 Meter",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Untuk menghubungkan perangkat pasif ke aktif",
                "minimum_stock": {
                    "main": 450,
                    "transit": 25,
                    "lastmile": 6
                }
            },
            {
                "number": 1000000775,
                "name": "Patchcord Fc/Upc - Sc/Upc Sx,Sm 5 Meter",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Untuk menghubungkan perangkat pasif ke aktif",
                "minimum_stock": {
                    "main": 450,
                    "transit": 25,
                    "lastmile": 6
                }
            },
            {
                "number": 1000000841,
                "name": "Patchcord Fc/Upc - Sc/Upc Sx,Sm 10 Meter",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Untuk menghubungkan perangkat pasif ke aktif",
                "minimum_stock": {
                    "main": 450,
                    "transit": 25,
                    "lastmile": 6
                }
            },
            {
                "number": 1000000324,
                "name": "Patchcord Fc/Upc - Sc/Upc Dx,Sm 10 Meter",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Untuk menghubungkan perangkat pasif ke aktif",
                "minimum_stock": {
                    "main": 450,
                    "transit": 25,
                    "lastmile": 6
                }
            },
            {
                "number": 1000000838,
                "name": "Patchcord Fc/Upc - Sc/Upc Sx,Sm 1,5 Meter",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Untuk menghubungkan perangkat pasif ke aktif",
                "minimum_stock": {
                    "main": 450,
                    "transit": 25,
                    "lastmile": 6
                }
            },
            {
                "number": 1000001129,
                "name": "Patchcord Fc/Upc - Lc/Upc Sx,Sm 5 Meter",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Untuk menghubungkan perangkat pasif ke aktif",
                "minimum_stock": {
                    "main": 450,
                    "transit": 25,
                    "lastmile": 6
                }
            },
            {
                "number": 1000000130,
                "name": "Patchcord Fc/Upc - Lc/Upc G655C 3 Meter",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Untuk menghubungkan perangkat pasif ke aktif",
                "minimum_stock": {
                    "main": 450,
                    "transit": 25,
                    "lastmile": 6
                }
            },
            {
                "number": 1000000776,
                "name": "Patchcord Sc/Upc - Sc/Upc Sx,Sm 5 Meter",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Untuk menghubungkan perangkat pasif ke aktif",
                "minimum_stock": {
                    "main": 450,
                    "transit": 25,
                    "lastmile": 6
                }
            },
            {
                "number": 1000000327,
                "name": "Patchcord Sc/Upc - Sc/Upc Dx,Sm 5 Meter",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Untuk menghubungkan perangkat pasif ke aktif",
                "minimum_stock": {
                    "main": 450,
                    "transit": 25,
                    "lastmile": 6
                }
            },
            {
                "number": 1000000817,
                "name": "Patchcord Sc/Upc - Sc/Upc Dx,Sm 50 Meter",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Untuk menghubungkan perangkat pasif ke aktif",
                "minimum_stock": {
                    "main": 450,
                    "transit": 25,
                    "lastmile": 6
                }
            },
            {
                "number": 1000000800,
                "name": "Patchcord Sc/Upc - Sc/Apc Sx,Sm 5 Meter",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Untuk menghubungkan perangkat pasif ke aktif",
                "minimum_stock": {
                    "main": 450,
                    "transit": 25,
                    "lastmile": 6
                }
            },
            {
                "number": 1000000988,
                "name": "Patchcord Sc/Apc - Sc/Apc Sx,Sm 5 Meter",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Untuk menghubungkan perangkat pasif ke aktif",
                "minimum_stock": {
                    "main": 450,
                    "transit": 25,
                    "lastmile": 6
                }
            },
            {
                "number": 1000001128,
                "name": "Patchcord Sc/Upc - Lc/Upc Sx,Sm 5 Meter",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Untuk menghubungkan perangkat pasif ke aktif",
                "minimum_stock": {
                    "main": 450,
                    "transit": 25,
                    "lastmile": 6
                }
            },
            {
                "number": 1000000131,
                "name": "Patchcord Sc/Upc - Lc/Upc Dx,Sm 3 Meter",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Untuk menghubungkan perangkat pasif ke aktif",
                "minimum_stock": {
                    "main": 450,
                    "transit": 25,
                    "lastmile": 6
                }
            },
            {
                "number": 1000000992,
                "name": "Patchcord Lc/Upc - Lc/Upc G652D",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Untuk menghubungkan perangkat pasif ke aktif",
                "minimum_stock": {
                    "main": 450,
                    "transit": 25,
                    "lastmile": 6
                }
            },
            {
                "number": 1000000970,
                "name": "Patchcord Lc/Upc - Lc/Apc 5-20M",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Untuk menghubungkan perangkat pasif ke aktif",
                "minimum_stock": {
                    "main": 450,
                    "transit": 25,
                    "lastmile": 6
                }
            },
            {
                "number": 1000000995,
                "name": "Adapter Fc/Upc - Fc/Upc",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Sebagai penyambung antara satu konektor/kabel fiber optic dengan konektor/kabel fiber optic lainnya",
                "minimum_stock": {
                    "main": 500,
                    "transit": 100,
                    "lastmile": 24
                }
            },
            {
                "number": 1000000989,
                "name": "Adapter Fc/Upc - Lc/Upc",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Sebagai penyambung antara satu konektor/kabel fiber optic dengan konektor/kabel fiber optic lainnya",
                "minimum_stock": {
                    "main": 500,
                    "transit": 100,
                    "lastmile": 24
                }
            },
            {
                "number": 1000001110,
                "name": "Adapter Sc/Upc - Sc/Upc",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Sebagai penyambung antara satu konektor/kabel fiber optic dengan konektor/kabel fiber optic lainnya",
                "minimum_stock": {
                    "main": 500,
                    "transit": 100,
                    "lastmile": 24
                }
            },
            {
                "number": 1000001106,
                "name": "Adapter Sc/Upc - Lc/Upc",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Sebagai penyambung antara satu konektor/kabel fiber optic dengan konektor/kabel fiber optic lainnya",
                "minimum_stock": {
                    "main": 500,
                    "transit": 100,
                    "lastmile": 24
                }
            },
            {
                "number": 1000001109,
                "name": "Adapter Sc/Apc - Sc/Apc",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Sebagai penyambung antara satu konektor/kabel fiber optic dengan konektor/kabel fiber optic lainnya",
                "minimum_stock": {
                    "main": 500,
                    "transit": 100,
                    "lastmile": 24
                }
            },
            {
                "number": 1000001107,
                "name": "Adapter Sc/Apc - Lc/Upc",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Sebagai penyambung antara satu konektor/kabel fiber optic dengan konektor/kabel fiber optic lainnya",
                "minimum_stock": {
                    "main": 500,
                    "transit": 100,
                    "lastmile": 24
                }
            },
            {
                "number": 1000000188,
                "name": "Connector",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": {
                    "main": 500,
                    "transit": 100,
                    "lastmile": 24
                }
            },
            {
                "number": 1000000881,
                "name": "Connector Fc/Upc",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": {
                    "main": 500,
                    "transit": 100,
                    "lastmile": 24
                }
            },
            {
                "number": 1000000882,
                "name": "Connector Sc/Upc",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": {
                    "main": 500,
                    "transit": 100,
                    "lastmile": 24
                }
            },
            {
                "number": 1000000791,
                "name": "Cable Termination Box",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000153,
                "name": "Electrode FSM-50/60S/17R/18R",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Konduktor yang digunakan untuk bersentuhan dengan bagian atau media non-logam dari sebuah sirkuit (misal semikonduktor, elektrolit atau vakum)",
                "minimum_stock": {
                    "main": 50,
                    "transit": 4,
                    "lastmile": 1
                }
            },
            {
                "number": 1000000645,
                "name": "Electrode Er-8 Type 37/37Se Sumitomo",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Konduktor yang digunakan untuk bersentuhan dengan bagian atau media non-logam dari sebuah sirkuit (misal semikonduktor, elektrolit atau vakum)",
                "minimum_stock": {
                    "main": 50,
                    "transit": 4,
                    "lastmile": 1
                }
            },
            {
                "number": 1000000584,
                "name": "Cleaver Blade Sumitomo",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Alat pemotong saat kulit dari core fiber optik sudah terkelupas",
                "minimum_stock": {
                    "main": 50,
                    "transit": 4,
                    "lastmile": 1
                }
            },
            {
                "number": 1000000155,
                "name": "Protection Sleeve",
                "category": "Pole & Accessories",
                "uom": "Pcs",
                "description": "Sebagai lapisan penguat di fokus titik penyambungan dan berperan sebagai lapisan untuk coating pengganti",
                "minimum_stock": {
                    "main": 10000,
                    "transit": 1000,
                    "lastmile": 200
                }
            },
            {
                "number": 1000000062,
                "name": "Mpjc 24 Core",
                "category": "Joint Closure",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000064,
                "name": "Mpjc 48 Core",
                "category": "Joint Closure",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000068,
                "name": "Uqj Adhesive Kit Kit35010",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000069,
                "name": "Uqj Nsw Adhesive Kit Kit35020",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000070,
                "name": "Uqj Armoured Protection Kit30021",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000071,
                "name": "Uqj Common Component Kit Kit320002",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000073,
                "name": "Uqj Nsw Minisub Ct Da Esk Kit31630",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000075,
                "name": "Uqj Nsw Minisub Ct Sa Esk Kit31620",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000105,
                "name": "Duct Anchor Standar K225",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000890,
                "name": "Anchor Flange",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000162,
                "name": "Articulate Pipe",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000163,
                "name": "Cable Protector Sa (Pack Isi 10)",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000164,
                "name": "Cable Protector Da (Pack Isi 10Set)",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000199,
                "name": "Preformed Grip/Cable Dead End Sa",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000200,
                "name": "Preformed Grip/Cable Dead End Da",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000291,
                "name": "Kabel Remote Kapal 28 Feet",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000346,
                "name": "Cement Bag",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000383,
                "name": "Gesper / Stoplink",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000643,
                "name": "Heat Shrink",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000864,
                "name": "Heatshrink 12/4",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000865,
                "name": "Heatshrink 20/8",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000644,
                "name": "Wire Rope 22 Mm 200M/Roll",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000647,
                "name": "Grapnel",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000669,
                "name": "Shackle Crosby 75 Ton",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000670,
                "name": "Shackle Crosby 50 Ton",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000676,
                "name": "Cable Protector Sa Tipe Od 65",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000679,
                "name": "Cable Protector Da Tipe Od 75",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000770,
                "name": "Minisub Da 48 24 X Smf2Ull",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000771,
                "name": "Minisub Sa 48 24 X Smf2Ull",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000852,
                "name": "Karet Wiper Kapal",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000862,
                "name": "Sus Glue",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000863,
                "name": "O-Ring Grease",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000866,
                "name": "Nut For Wire Organizer",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000867,
                "name": "Pressure Ring Protection",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000869,
                "name": "Main Connecting Ring",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000870,
                "name": "Reducer Nut Cable Diameter",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000891,
                "name": "Flexible Rubber Horse",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000955,
                "name": "Besi Pin Pengunci Diameter 10Mm",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            },
            {
                "number": 1000000418,
                "name": "Wire Lock 500 Cc",
                "category": "Submarine",
                "uom": "Pcs",
                "description": "",
                "minimum_stock": ""
            }
        ]';

        $data = json_decode($json);
        DB::beginTransaction();
        try {
            foreach ($data as $item) {
                $category = Category::where('name', $item->category)->first();
                if (!$category) {
                    $category = new Category();
                    $category->name = $item->category;
                    $category->save();
                }
                $material = new Material();
                $material->category_id = $category->id;
                $material->name = $item->name;
                $material->number = $item->number;
                $material->uom = $item->uom;
                $material->description = $item->description;
                $material->minimum_stock = $item->minimum_stock;
                $material->save();
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

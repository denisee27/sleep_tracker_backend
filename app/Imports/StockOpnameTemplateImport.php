<?php

namespace App\Imports;

use App\Models\StockOpnameDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StockOpnameTemplateImport implements
    ToCollection,
    WithChunkReading,
    WithHeadingRow,
    SkipsEmptyRows,
    WithCalculatedFormulas,
    WithValidation
{
    /**
     * collection
     *
     * @param  mixed $collection
     * @return void
     */
    public function collection(Collection $collection)
    {
        DB::beginTransaction();
        try {
            foreach ($collection as $item) {
                $stock = StockOpnameDetail::findOrFail($item['id']);
                $stock->counted_good_qty = (int)$item['counted_good_qty'];
                $stock->counted_bad_qty = (int)$item['counted_bad_qty'];
                $stock->counted_lost_qty = (int)$item['counted_lost_qty'];
                $stock->notes = $item['notes'];
                $stock->save();
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * chunkSize
     *
     * @return int
     */
    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * headingRow
     *
     * @return int
     */
    public function headingRow(): int
    {
        return 1;
    }

    /**
     * rules
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'string', Rule::exists(StockOpnameDetail::class, 'id')],
            'counted_good_qty' => 'nullable|numeric|min:0',
            'counted_bad_qty' => 'nullable|numeric|min:0',
            'counted_lost_qty' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ];
    }
}

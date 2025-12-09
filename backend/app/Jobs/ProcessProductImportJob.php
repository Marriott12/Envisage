<?php

namespace App\Jobs;

use App\Models\ProductImport;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProcessProductImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $import;
    public $filePath;

    public function __construct($importId, $filePath)
    {
        $this->import = ProductImport::findOrFail($importId);
        $this->filePath = $filePath;
    }

    public function handle()
    {
        $this->import->markAsProcessing();

        try {
            $file = Storage::disk('local')->get($this->filePath);
            $rows = array_map('str_getcsv', explode("\n", $file));
            $header = array_shift($rows);

            $this->import->update(['total_rows' => count($rows)]);

            foreach ($rows as $index => $row) {
                if (count($row) !== count($header)) {
                    continue; // Skip malformed rows
                }

                $data = array_combine($header, $row);
                $this->processRow($data, $index + 2);
                
                $this->import->increment('processed_rows');
            }

            $this->import->markAsCompleted();

        } catch (\Exception $e) {
            $this->import->markAsFailed($e->getMessage());
        }
    }

    protected function processRow($data, $rowNumber)
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'sku' => 'nullable|string|unique:products,sku',
        ]);

        if ($validator->fails()) {
            $this->import->addError($rowNumber, $validator->errors()->first());
            $this->import->increment('failed_rows');
            return;
        }

        try {
            Product::create([
                'seller_id' => $this->import->user_id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'category_id' => $data['category_id'],
                'sku' => $data['sku'] ?? null,
                'brand' => $data['brand'] ?? null,
                'condition' => $data['condition'] ?? 'new',
                'inventory_count' => $data['inventory_count'] ?? 0,
                'status' => 'active',
            ]);

            $this->import->increment('successful_rows');

        } catch (\Exception $e) {
            $this->import->addError($rowNumber, $e->getMessage());
            $this->import->increment('failed_rows');
        }
    }
}

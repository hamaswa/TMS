<?php

namespace App\Services;

use App\Models\ClothColor;
use App\Models\InventoryMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function receive(ClothColor $color, float $quantity, float $unitCost, string $type, Model $reference, ?string $note = null, bool $recalculateCost = true): InventoryMovement
    {
        $quantity = round($quantity, 2);
        $oldQuantity = (float) $color->length;
        $oldCost = (float) $color->average_unit_cost;
        $newQuantity = round($oldQuantity + $quantity, 2);
        $newCost = $oldCost;
        if ($recalculateCost && $newQuantity > 0) {
            $newCost = round((($oldQuantity * $oldCost) + ($quantity * $unitCost)) / $newQuantity, 4);
        }
        $color->update(['length' => $newQuantity, 'average_unit_cost' => $newCost]);

        return $this->movement($color, $type, $quantity, $unitCost, $reference, $note);
    }

    public function issue(ClothColor $color, float $quantity, string $type, Model $reference, ?string $note = null, ?float $unitCost = null): InventoryMovement
    {
        $quantity = round($quantity, 2);
        if ((float) $color->length < $quantity) {
            throw ValidationException::withMessages(['quantity' => 'اتنی مقدار اسٹاک میں موجود نہیں ہے۔']);
        }
        $color->update(['length' => round((float) $color->length - $quantity, 2)]);

        return $this->movement($color, $type, -$quantity, $unitCost ?? (float) $color->average_unit_cost, $reference, $note);
    }

    public function restore(ClothColor $color, float $quantity, string $type, Model $reference, ?string $note = null): InventoryMovement
    {
        return $this->receive($color, $quantity, (float) $color->average_unit_cost, $type, $reference, $note, false);
    }

    private function movement(ClothColor $color, string $type, float $quantity, float $unitCost, Model $reference, ?string $note): InventoryMovement
    {
        return InventoryMovement::create([
            'user_id' => $color->user_id,
            'cloth_id' => $color->cloth_id,
            'cloth_color_id' => $color->id,
            'movement_type' => $type,
            'quantity' => $quantity,
            'balance_after' => $color->length,
            'unit_cost' => $unitCost,
            'reference_type' => $reference::class,
            'reference_id' => $reference->getKey(),
            'note' => $note,
            'occurred_at' => now(),
        ]);
    }
}

<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Armada extends Model
{
    protected $fillable = ['vendor_id', 'nopol', 'panjang', 'lebar', 'tinggi'];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}

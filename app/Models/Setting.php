<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable =['user_id','name','logo','note','address','status','contact','shop_slug'];

    public function getLogoUrlAttribute(): ?string
    {
        $filename = basename(str_replace('\\', '/', (string) $this->logo));
        if ($filename === '' || ! is_file(public_path('images/setting/'.$filename))) {
            return null;
        }

        return asset('images/setting/'.$filename);
    }
}

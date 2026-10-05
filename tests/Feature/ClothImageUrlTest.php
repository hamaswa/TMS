<?php

namespace Tests\Feature;

use App\Models\ClothImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ClothImageUrlTest extends TestCase
{
    public function test_public_upload_uses_the_active_app_origin_instead_of_the_disk_origin(): void
    {
        Storage::fake('public');
        config(['filesystems.disks.public.url' => 'http://localhost/storage']);
        URL::forceRootUrl('http://127.0.0.1:8002');
        Storage::disk('public')->put('ClothImages/fabric.jpg', 'image');

        $image = new ClothImage(['images' => 'ClothImages/fabric.jpg']);

        $this->assertSame('http://127.0.0.1:8002/storage/ClothImages/fabric.jpg', $image->image_url);
    }

    public function test_missing_upload_does_not_generate_a_broken_image_url(): void
    {
        Storage::fake('public');

        $this->assertNull((new ClothImage(['images' => 'ClothImages/missing.jpg']))->image_url);
        $this->assertNull((new ClothImage())->image_url);
    }
}

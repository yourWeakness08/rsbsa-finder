<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class StorageWithLink
{
    protected static function ensureSymlink()
    {
        $link = public_path('storage');
        $target = storage_path('app/public');

        if (!file_exists($link) || (is_link($link) && !file_exists(readlink($link)))) {
            @unlink($link); // Remove broken symlink
            symlink($target, $link);
        }
    }

    public static function put($path, $contents, $options = [])
    {
        self::ensureSymlink();
        return Storage::disk('public')->put($path, $contents, $options);
    }

    public static function putFile($path, $file, $options = [])
    {
        self::ensureSymlink();
        return Storage::disk('public')->putFile($path, $file, $options);
    }

    public static function putFileAs($path, $file, $name, $options = [])
    {
        self::ensureSymlink();
        return Storage::disk('public')->putFileAs($path, $file, $name, $options);
    }

    // Add more methods if needed...
}

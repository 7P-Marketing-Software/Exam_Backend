<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HasDigitalOceanSpaces
{
    /**
     * Upload file to Digital Ocean Spaces with module folder structure
     */
    protected function uploadToSpaces($file, $module, $subfolder = '', $filename = null)
    {
        $extension = $file->getClientOriginalExtension();
        $filename = $filename ?: Str::random(40) . '.' . $extension;
        
        $folderPath = "{$module}";
        if (!empty($subfolder)) {
            $folderPath .= "/{$subfolder}";
        }
        
        $filePath = "{$folderPath}/{$filename}";
        
        // Store file in Digital Ocean Spaces
        Storage::disk('spaces')->put($filePath, file_get_contents($file), 'public');
        
        // Return full URL to the file
        return Storage::disk('spaces')->url($filePath);
    }

    /**
     * Delete file from Digital Ocean Spaces
     */
    protected function deleteFromSpaces($fileUrl)
    {
        if (!$fileUrl) {
            return;
        }

        // Extract file path from URL
        $urlPath = parse_url($fileUrl, PHP_URL_PATH);
        $filePath = ltrim($urlPath, '/');
        
        // Delete file from Spaces
        if (Storage::disk('spaces')->exists($filePath)) {
            Storage::disk('spaces')->delete($filePath);
        }
    }

    /**
     * Delete multiple files from Digital Ocean Spaces
     */
    protected function deleteMultipleFromSpaces($fileUrls)
    {
        if (!is_array($fileUrls) || empty($fileUrls)) {
            return;
        }

        foreach ($fileUrls as $fileUrl) {
            $this->deleteFromSpaces($fileUrl);
        }
    }

    /**
     * Extract file path from URL
     */
    protected function getFilePathFromUrl($fileUrl)
    {
        if (!$fileUrl) {
            return null;
        }

        $urlPath = parse_url($fileUrl, PHP_URL_PATH);
        return ltrim($urlPath, '/');
    }
}

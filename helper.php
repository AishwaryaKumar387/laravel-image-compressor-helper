<?php 
// CUSTOM IMAGE COMPRESSOR 
    /**
     * Compress and resize an uploaded image based on the specified maximum width and quality.
     * 
     * @param array  $file          The uploaded file information (e.g., $_FILES).
     * @param string $destination   The destination path where the compressed image will be saved.
     * @param int    $maxImgWidth   The maximum width for resizing the image.
     * @param int    $quality       The quality for image compression (0-100 for JPEG, 0-9 for PNG).
     * 
     * @return array  An associative array containing the status and details of the operation, including:
     *                - status (string): 'success' or 'failure'.
     *                - message (string): A descriptive message.
     *                - original_size (string): The size of the original image.
     *                - compressed_size (string): The size of the compressed image.
     *                - destination (string): The destination path of the compressed image.
     */
    public function ignitedCompressAndResizeImage($file, $maxImgWidth, $quality)
    {
        // Get the MIME type of the file
        $mime = $file->getMimeType();
        
        // Define the upload directory
        $uploadDir = public_path('uploads/compressed-images/');

        // Ensure the upload directory exists, if not, create it
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true); // 0755 permissions and recursive creation
        }

        // Generate the destination file path
        $destination = $uploadDir . uniqid() . '.' . $file->getClientOriginalExtension();

        // Create image resource based on MIME type
        switch ($mime) {
            case 'image/jpeg':
                $src = imagecreatefromjpeg($file->getRealPath());
                $save_function = 'imagejpeg';
                break;
            case 'image/png':
                $src = imagecreatefrompng($file->getRealPath());
                $save_function = 'imagepng';
                break;
            case 'image/gif':
                $src = imagecreatefromgif($file->getRealPath());
                $save_function = 'imagegif';
                break;
            case 'image/webp':
                $src = imagecreatefromwebp($file->getRealPath());
                $save_function = 'imagewebp';
                break;
            case 'image/avif':
                $src = imagecreatefromavif($file->getRealPath());
                $save_function = 'imageavif';
                break;
            default:
                $save_function = '';
        }

        if (!empty($save_function)) {
            // Get the original dimensions of the uploaded image
            list($width, $height) = getimagesize($file->getRealPath());

            // If resizing is needed
            if ($width > $maxImgWidth) {
                // Calculate the new dimensions maintaining the aspect ratio
                $newwidth = $maxImgWidth;
                $newheight = (int)(($height / $width) * $newwidth);

                // Create a new image with the new dimensions
                $newImage = imagecreatetruecolor($newwidth, $newheight);

                // Preserve transparency for PNG, GIF, and WebP
                if ($mime == 'image/png' || $mime == 'image/gif' || $mime == 'image/webp') {
                    imagealphablending($newImage, false);
                    imagesavealpha($newImage, true);
                }

                // Resample the image to the new size
                imagecopyresampled($newImage, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

                // Save the resized and compressed image
                if ($mime == 'image/jpeg') {
                    // For JPEG, use quality 0-100
                    $save_function($newImage, $destination, $quality);
                } elseif ($mime == 'image/png') {
                    // For PNG, use compression level 0-9 (quality 0-9)
                    $save_function($newImage, $destination, floor($quality / 10)); // Compression level
                } elseif ($mime == 'image/gif' || $mime == 'image/webp' || $mime == 'image/avif') {
                    // For GIF, WebP, and AVIF, use the default compression method (without quality level)
                    $save_function($newImage, $destination);
                }

                // Free up memory
                imagedestroy($src);
                imagedestroy($newImage);

                // Prepare response data in JSON format
                $response = array(
                    'status' => true,
                    'message' => 'Image resized and compressed successfully.',
                    'original_size' => ignited_convert_filesize(filesize($file->getRealPath())),
                    'compressed_size' => ignited_convert_filesize(filesize($destination)),
                    'destination' => $destination,
                );

                // Return the response as a JSON object
                return $response;
            }
        }

        // Prepare failure response if no resizing was needed
        $response = array(
            'status' => false,
            'message' => 'No resizing needed for the image.',
        );

        // Return failure response
        return $response;
    }

    /**
     * Convert a file size in bytes to a human-readable format.
     * 
     * @param int    $bytes     The size of the file in bytes.
     * @param int    $decimals  The number of decimals to include in the output (default is 2).
     * 
     * @return string  The human-readable file size with the appropriate unit (e.g., KB, MB).
     */
    public function ignited_convert_filesize($bytes, $decimals = 2) {
        $size = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }

<?php
session_start();
require_once '../config/database.php';

// بررسی احراز هویت
if (!isset($_SESSION['admin_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('دسترسی غیرمجاز');
}

$temp_dir = '../uploads/temp';
if (!file_exists($temp_dir)) {
    mkdir($temp_dir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chunk = isset($_FILES['file']) ? $_FILES['file'] : null;
    $identifier = $_POST['resumableIdentifier'];
    $chunkNumber = $_POST['resumableChunkNumber'];
    $totalChunks = $_POST['resumableTotalChunks'];
    
    if ($chunk && $chunk['error'] === UPLOAD_ERR_OK) {
        $temp_file = $temp_dir . '/' . $identifier . '.' . $chunkNumber;
        move_uploaded_file($chunk['tmp_name'], $temp_file);
        
        // بررسی اتمام آپلود
        $all_chunks_uploaded = true;
        for ($i = 1; $i <= $totalChunks; $i++) {
            if (!file_exists($temp_dir . '/' . $identifier . '.' . $i)) {
                $all_chunks_uploaded = false;
                break;
            }
        }
        
        if ($all_chunks_uploaded) {
            // ترکیب چانک‌ها
            $final_file = '../uploads/' . $identifier;
            $out = fopen($final_file, 'wb');
            
            for ($i = 1; $i <= $totalChunks; $i++) {
                $in = fopen($temp_dir . '/' . $identifier . '.' . $i, 'rb');
                stream_copy_to_stream($in, $out);
                fclose($in);
                unlink($temp_dir . '/' . $identifier . '.' . $i);
            }
            
            fclose($out);
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => true, 'chunk' => $chunkNumber]);
        }
    }
} else {
    header('HTTP/1.1 400 Bad Request');
    exit('درخواست نامعتبر');
} 
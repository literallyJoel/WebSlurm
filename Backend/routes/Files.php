<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Files
{
    //Used for getting the correct file extension for a given mime type
    //Taken from https://stackoverflow.com/questions/16511021/convert-mime-type-to-file-extension-php
    private function getExtension($mime)
    {
        $mime_map = [
            'video/3gpp2' => '3g2',
            'video/3gp' => '3gp',
            'video/3gpp' => '3gp',
            'application/x-compressed' => '7zip',
            'audio/x-acc' => 'aac',
            'audio/ac3' => 'ac3',
            'application/postscript' => 'ai',
            'audio/x-aiff' => 'aif',
            'audio/aiff' => 'aif',
            'audio/x-au' => 'au',
            'video/x-msvideo' => 'avi',
            'video/msvideo' => 'avi',
            'video/avi' => 'avi',
            'application/x-troff-msvideo' => 'avi',
            'application/macbinary' => 'bin',
            'application/mac-binary' => 'bin',
            'application/x-binary' => 'bin',
            'application/x-macbinary' => 'bin',
            'image/bmp' => 'bmp',
            'image/x-bmp' => 'bmp',
            'image/x-bitmap' => 'bmp',
            'image/x-xbitmap' => 'bmp',
            'image/x-win-bitmap' => 'bmp',
            'image/x-windows-bmp' => 'bmp',
            'image/ms-bmp' => 'bmp',
            'image/x-ms-bmp' => 'bmp',
            'application/bmp' => 'bmp',
            'application/x-bmp' => 'bmp',
            'application/x-win-bitmap' => 'bmp',
            'application/cdr' => 'cdr',
            'application/coreldraw' => 'cdr',
            'application/x-cdr' => 'cdr',
            'application/x-coreldraw' => 'cdr',
            'image/cdr' => 'cdr',
            'image/x-cdr' => 'cdr',
            'zz-application/zz-winassoc-cdr' => 'cdr',
            'application/mac-compactpro' => 'cpt',
            'application/pkix-crl' => 'crl',
            'application/pkcs-crl' => 'crl',
            'application/x-x509-ca-cert' => 'crt',
            'application/pkix-cert' => 'crt',
            'text/css' => 'css',
            'text/x-comma-separated-values' => 'csv',
            'text/comma-separated-values' => 'csv',
            'application/vnd.msexcel' => 'csv',
            'application/x-director' => 'dcr',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/x-dvi' => 'dvi',
            'message/rfc822' => 'eml',
            'application/x-msdownload' => 'exe',
            'video/x-f4v' => 'f4v',
            'audio/x-flac' => 'flac',
            'video/x-flv' => 'flv',
            'image/gif' => 'gif',
            'application/gpg-keys' => 'gpg',
            'application/x-gtar' => 'gtar',
            'application/x-gzip' => 'gzip',
            'application/mac-binhex40' => 'hqx',
            'application/mac-binhex' => 'hqx',
            'application/x-binhex40' => 'hqx',
            'application/x-mac-binhex40' => 'hqx',
            'text/html' => 'html',
            'image/x-icon' => 'ico',
            'image/x-ico' => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
            'text/calendar' => 'ics',
            'application/java-archive' => 'jar',
            'application/x-java-application' => 'jar',
            'application/x-jar' => 'jar',
            'image/jp2' => 'jp2',
            'video/mj2' => 'jp2',
            'image/jpx' => 'jp2',
            'image/jpm' => 'jp2',
            'image/jpeg' => 'jpeg',
            'image/pjpeg' => 'jpeg',
            'application/x-javascript' => 'js',
            'application/json' => 'json',
            'text/json' => 'json',
            'application/vnd.google-earth.kml+xml' => 'kml',
            'application/vnd.google-earth.kmz' => 'kmz',
            'text/x-log' => 'log',
            'audio/x-m4a' => 'm4a',
            'audio/mp4' => 'm4a',
            'application/vnd.mpegurl' => 'm4u',
            'audio/midi' => 'mid',
            'application/vnd.mif' => 'mif',
            'video/quicktime' => 'mov',
            'video/x-sgi-movie' => 'movie',
            'audio/mpeg' => 'mp3',
            'audio/mpg' => 'mp3',
            'audio/mpeg3' => 'mp3',
            'audio/mp3' => 'mp3',
            'video/mp4' => 'mp4',
            'video/mpeg' => 'mpeg',
            'application/oda' => 'oda',
            'audio/ogg' => 'ogg',
            'video/ogg' => 'ogg',
            'application/ogg' => 'ogg',
            'font/otf' => 'otf',
            'application/x-pkcs10' => 'p10',
            'application/pkcs10' => 'p10',
            'application/x-pkcs12' => 'p12',
            'application/x-pkcs7-signature' => 'p7a',
            'application/pkcs7-mime' => 'p7c',
            'application/x-pkcs7-mime' => 'p7c',
            'application/x-pkcs7-certreqresp' => 'p7r',
            'application/pkcs7-signature' => 'p7s',
            'application/pdf' => 'pdf',
            'application/octet-stream' => 'pdf',
            'application/x-x509-user-cert' => 'pem',
            'application/x-pem-file' => 'pem',
            'application/pgp' => 'pgp',
            'application/x-httpd-php' => 'php',
            'application/php' => 'php',
            'application/x-php' => 'php',
            'text/php' => 'php',
            'text/x-php' => 'php',
            'application/x-httpd-php-source' => 'php',
            'image/png' => 'png',
            'image/x-png' => 'png',
            'application/powerpoint' => 'ppt',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.ms-office' => 'ppt',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/x-photoshop' => 'psd',
            'image/vnd.adobe.photoshop' => 'psd',
            'audio/x-realaudio' => 'ra',
            'audio/x-pn-realaudio' => 'ram',
            'application/x-rar' => 'rar',
            'application/rar' => 'rar',
            'application/x-rar-compressed' => 'rar',
            'audio/x-pn-realaudio-plugin' => 'rpm',
            'application/x-pkcs7' => 'rsa',
            'text/rtf' => 'rtf',
            'text/richtext' => 'rtx',
            'video/vnd.rn-realvideo' => 'rv',
            'application/x-stuffit' => 'sit',
            'application/smil' => 'smil',
            'text/srt' => 'srt',
            'image/svg+xml' => 'svg',
            'application/x-shockwave-flash' => 'swf',
            'application/x-tar' => 'tar',
            'application/x-gzip-compressed' => 'tgz',
            'image/tiff' => 'tiff',
            'font/ttf' => 'ttf',
            'text/plain' => 'txt',
            'text/x-vcard' => 'vcf',
            'application/videolan' => 'vlc',
            'text/vtt' => 'vtt',
            'audio/x-wav' => 'wav',
            'audio/wave' => 'wav',
            'audio/wav' => 'wav',
            'application/wbxml' => 'wbxml',
            'video/webm' => 'webm',
            'image/webp' => 'webp',
            'audio/x-ms-wma' => 'wma',
            'application/wmlc' => 'wmlc',
            'video/x-ms-wmv' => 'wmv',
            'video/x-ms-asf' => 'wmv',
            'font/woff' => 'woff',
            'font/woff2' => 'woff2',
            'application/xhtml+xml' => 'xhtml',
            'application/excel' => 'xl',
            'application/msexcel' => 'xls',
            'application/x-msexcel' => 'xls',
            'application/x-ms-excel' => 'xls',
            'application/x-excel' => 'xls',
            'application/x-dos_ms_excel' => 'xls',
            'application/xls' => 'xls',
            'application/x-xls' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-excel' => 'xlsx',
            'application/xml' => 'xml',
            'text/xml' => 'xml',
            'text/xsl' => 'xsl',
            'application/xspf+xml' => 'xspf',
            'application/x-compress' => 'z',
            'application/x-zip' => 'zip',
            'application/zip' => 'zip',
            'application/x-zip-compressed' => 'zip',
            'application/s-compressed' => 'zip',
            'multipart/x-zip' => 'zip',
            'text/x-scriptzsh' => 'zsh',
        ];

        return $mime_map[$mime] ?? false;
    }


    //Downloads a requested file
    private function downloadFile($filePath, $response)
    {
        if (!file_exists($filePath)) {
            $response->getBody()->write("File not found");
            return $response->withStatus(404);
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($filePath);
        $response = $response->withHeader('Content-Type', $mime);
        $response = $response->withHeader('Content-Disposition', 'attachment; filename="' . basename($filePath) . "." . $this->getExtension($mime) . '"');

        $response->getBody()->write(file_get_contents($filePath));
        return $response->withStatus(200);
    }

    //Downloads an input file if there's only one
    private function downloadSingleInputFile($response, $fileId, $userId)
    {
        $filePath = __DIR__ . "/../usr/in/$userId/$fileId";
        return $this->downloadFile($filePath, $response);
    }

    private function getFolderContents($folderPath, $response)
    {

        $files = scandir($folderPath);
        //Filter out the . and .. directories
        $files = array_filter($files, function ($file) {
            return $file !== "." && $file !== "..";
        });
        $result = [];
        foreach ($files as $file) {
            $fileName = pathinfo($file, PATHINFO_FILENAME);
            if (!empty($filename)) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $filePath = (substr($folderPath, -1) === "/" ? $folderPath : $folderPath . "/") . $file;
                $mime = $finfo->file($filePath);
                $ext = $this->getExtension($mime);
                if ($ext !== false) $result[] = ["fileName" => $fileName, "fileExtension" => $ext];
            }
        }

        $response->getBody()->write(json_encode($result));
        return $response->withStatus(200);
    }

    //Downloads a folders contents
    private function downloadFolderContents($response, $folderPath, $jobId)
    {
        //Check the folder exists
        if (!file_exists($folderPath)) {
            $response->getBody()->write("Folder not found");
            return $response->withStatus(404);
        }

        //Zip the contents of the folder
        $zip = new ZipArchive();
        $zipPath = __DIR__ . "/../usr/out/$jobId.zip";
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            $response->getBody()->write("Failed to create zip file");
            return $response->withStatus(500);
        }
        //Add all files in the folder to the zip
        $files = scandir($folderPath);
        foreach ($files as $file) {
            $filePath = (substr($folderPath, -1) === "/" ? $folderPath : $folderPath . "/") . $file;
            $zip->addFile($filePath, $file);
        }
        $zip->close();
        //Download the zip file
        $resp = $this->downloadFile($zipPath, $response);
        //Delete the zip file - makes sure the zip file always has the latest contents
        unlink($zipPath);
        return $resp;
    }

    //Gets the contents of a zip folder
    private function getZipContents($filePath, $response)
    {
        if (!file_exists($filePath)) {
            $response->getBody()->write("File not found");
            return $response->withStatus(404);
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($filePath);
        if ($mime !== "application/zip") {
            $response->getBody()->write("File is not a zip file");
            return $response->withStatus(400);
        }
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== TRUE) {
            $response->getBody()->write("Failed to open zip file");
            return $response->withStatus(500);
        }
        $result = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $fileName = pathinfo($stat['name'], PATHINFO_FILENAME);
            $ext = pathinfo($stat['name'], PATHINFO_EXTENSION);
            if (!empty($fileName)) {
                $result[] = ["fileName" => $fileName, "fileExtension" => $ext];
            }
        }
        $zip->close();
        $response->getBody()->write(json_encode($result));
        return $response->withStatus(200);
    }

    //====================Input Files====================//
    public function downloadInputFile(Request $request, Response $Response, array $args): Response
    {
        $decoded = $request->getAttribute("decoded");
        $userId = $decoded->userID;
        $jobId = $args["jobId"];
        $arrayId = $args["arrayId"] ?? null;

        $pdo = new PDO(DB_CONN);
        $stmt = $pdo->prepare("SELECT fileID FROM jobs WHERE jobID = :jobId AND userID = :userId");
        $stmt->execute(["jobId" => $jobId, "userId" => $userId]);
        $fileId = $stmt->fetchColumn();
        if (!$fileId) {
            $Response->getBody()->write("Job not found");
            return $Response->withStatus(404);
        }

        //Check how many files there are in the user input folder containing the fileId
        $folderPath = __DIR__ . "/../usr/in/$userId";
        $files = scandir($folderPath);
        $files = array_filter($files, function ($file) use ($fileId) {
            return strpos($file, $fileId) !== false;
        });

        if (count($files) === 1) {
            return $this->downloadSingleInputFile($Response, $fileId, $userId);
        } else {
            if ($arrayId === null) {
                //Grab all files containing the fileId and zip them into one zip file for download
                $zip = new ZipArchive();
                $zipPath = __DIR__ . "/../usr/out/$fileId-archive.zip";
                if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
                    $Response->getBody()->write("Failed to create zip file");
                    return $Response->withStatus(500);
                }
                foreach ($files as $file) {
                    $filePath = $folderPath . "/" . $file;
                    $zip->addFile($filePath, $file);
                }
                $zip->close();
                //Download the zip file
                $resp = $this->downloadFile($zipPath, $Response);
                //Delete the zip file - makes sure the zip file always has the latest contents
                unlink($zipPath);
                return $resp;
            } else {
                //If an arrayId is provided, download the file with the arrayId
                $filePath = $folderPath . "/" . $fileId . "-" . $arrayId;
                return $this->downloadFile($filePath, $Response);
            }
        }
    }

    public function getInputMetadata(Request $request, Response $response, array $args)
    {
        $decoded = $request->getAttribute("decoded");
        $userId = $decoded->userID;
        $jobId = $args["jobId"];
        $arrayId = $args["arrayId"] ?? null;
        $folderPath = __DIR__ . "/../usr/in/$userId";
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        $pdo = new PDO(DB_CONN);
        $stmt = $pdo->prepare("SELECT fileID FROM jobs WHERE jobID = :jobId AND userID = :userId");
        $stmt->execute(["jobId" => $jobId, "userId" => $userId]);
        $fileId = $stmt->fetchColumn();

        if (!$fileId) {
            $response->getBody()->write("Job not found");
            return $response->withStatus(404);
        }

        $filePath = $folderPath . "/" . $fileId;
        if ($arrayId) {
            $filePath .= "-" . $arrayId;
        }
        $mime = $finfo->file($filePath);
        if ($mime !== "application/zip") {
            $ext = $this->getExtension($mime);
            $resp = ["fileName" => pathinfo($filePath, PATHINFO_FILENAME), "fileExtension" => $ext];
            $response->getBody()->write(json_encode($resp));
            return $response->withStatus(200);
        } else {
            return $this->getZipContents($filePath, $response);
        }
    }

    //====================Output Files====================//
    public function downloadOutputFile(Request $request, Response $response, array $args)
    {
        $decoded = $request->getAttribute("decoded");
        $userId = $decoded->userID;
        $jobId = $args["jobId"];
        $fileName = $args["fileName"] ?? null;
        $pdo = new PDO(DB_CONN);
        $stmt = $pdo->prepare("SELECT * FROM jobs WHERE jobID = :jobId AND userID = :userId");
        $stmt->execute(["jobId" => $jobId, "userId" => $userId]);
        $job = $stmt->fetch();
        if (!$job) {
            $response->getBody()->write("Job not found");
            return $response->withStatus(404);
        }

        $outDir = __DIR__ . "/..usr/out/$userId/$jobId";
        if (!file_exists($outDir)) {
            $response->getBody()->write("Output folder not found");
            return $response->withStatus(404);
        }

        //Get the number of files in the outdir
        $files = scandir($outDir);
        $files = array_filter($files, function ($file) {
            return $file !== "." && $file !== "..";
        });

        if (count($files) === 1) {
            $filePath = $outDir . "/" . $files[0];
            if ($fileName) {
                if ($fileName !== pathinfo($filePath, PATHINFO_FILENAME)) {
                    $response->getBody()->write("File not found");
                    return $response->withStatus(404);
                }
            }
            return $this->downloadFile($filePath, $response);
        } else {
            if ($fileName) {
                $filePath = $outDir . "/" . $fileName;
                return $this->downloadFile($filePath, $response);
            }
            return $this->downloadFolderContents($response, $outDir, $jobId);
        }
    }

    public function getOutputMetadata(Request $request, Response $response, array $args)
    {
        $decoded = $request->getAttribute("decoded");
        $userId = $decoded->userID;
        $jobId = $args["jobId"];
        $fileName = $args["fileName"] ?? null;
        $pdo = new PDO(DB_CONN);
        $stmt = $pdo->prepare("SELECT * FROM jobs WHERE jobID = :jobId AND userID = :userId");
        $stmt->execute(["jobId" => $jobId, "userId" => $userId]);
        $job = $stmt->fetch();
        if (!$job) {
            $response->getBody()->write("Job not found");
            return $response->withStatus(404);
        }

        $outdir = __DIR__ . "/../usr/out/$userId/$jobId";
        if ($fileName) {
            $outdir .= "/" . $fileName;
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($outdir);
            if ($mime !== "application/zip") {
                $ext = $this->getExtension($mime);
                $resp = ["fileName" => pathinfo($outdir, PATHINFO_FILENAME), "fileExtension" => $ext];
                $response->getBody()->write(json_encode($resp));
                return $response->withStatus(200);
            }
            return $this->getZipContents($outdir, $response);
        }
        return $this->getFolderContents($outdir, $response);
    }

    //====================File Upload====================//
    public function handleFileUpload(Request $request, Response $response): Response
    {
        try {
            //Grab the users information from their decoded token
            $decodedToken = $request->getAttribute("decoded");
            //Grab the user ID to store with the job type
            $userID = $decodedToken->userID;


            $path = __DIR__ . "/../usr/in/$userID/";
            if (!file_exists($path)) {
                mkdir($path, 0775, true);
            }

            $this->server->setUploadDir($path);
            $psr17Factory = new Psr17Factory();
            $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

            $resp = $this->server->serve();
            return $psrHttpFactory->createResponse($resp);

        } catch (Exception $e) {
            Logger::error($e, "Jobs/handleFileUpload");
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }
}


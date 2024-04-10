<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use TusPhp\Tus\Server as TusServer;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

class Files
{
    private $server;

    public function __construct()
    {
        $this->server = new TusServer();
        $this->server->setMaxUploadSize(0)->setApiPath('/api/files/upload');
    }

    //===========================================================================//
    //=============================Helper Functions=============================//
    //=========================================================================//
    //Used for getting the correct file extension for a given mime type
    //Taken from https://stackoverflow.com/questions/16511021/convert-mime-type-to-file-extension-php
    private function getExtension($mime)
    {
        $mime_map =
            ['video/3gpp2' => '3g2', 'video/3gp' => '3gp', 'video/3gpp' => '3gp', 'application/x-compressed' => '7zip', 'audio/x-acc' => 'aac', 'audio/ac3' => 'ac3', 'application/postscript' => 'ai',
                'audio/x-aiff' => 'aif', 'audio/aiff' => 'aif', 'audio/x-au' => 'au', 'video/x-msvideo' => 'avi', 'video/msvideo' => 'avi', 'video/avi' => 'avi', 'application/x-troff-msvideo' => 'avi', 'application/macbinary' => 'bin',
                'application/mac-binary' => 'bin', 'application/x-binary' => 'bin', 'application/x-macbinary' => 'bin', 'image/bmp' => 'bmp', 'image/x-bmp' => 'bmp', 'image/x-bitmap' => 'bmp', 'image/x-xbitmap' => 'bmp', 'image/x-win-bitmap' => 'bmp',
                'image/x-windows-bmp' => 'bmp', 'image/ms-bmp' => 'bmp', 'image/x-ms-bmp' => 'bmp', 'application/bmp' => 'bmp', 'application/x-bmp' => 'bmp', 'application/x-win-bitmap' => 'bmp', 'application/cdr' => 'cdr', 'application/coreldraw' => 'cdr',
                'application/x-cdr' => 'cdr', 'application/x-coreldraw' => 'cdr', 'image/cdr' => 'cdr', 'image/x-cdr' => 'cdr', 'zz-application/zz-winassoc-cdr' => 'cdr', 'application/mac-compactpro' => 'cpt', 'application/pkix-crl' => 'crl',
                'application/pkcs-crl' => 'crl', 'application/x-x509-ca-cert' => 'crt', 'application/pkix-cert' => 'crt', 'text/css' => 'css', 'text/x-comma-separated-values' => 'csv', 'text/comma-separated-values' => 'csv',
                'application/vnd.msexcel' => 'csv', 'application/x-director' => 'dcr', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx', 'application/x-dvi' => 'dvi', 'message/rfc822' => 'eml',
                'application/x-msdownload' => 'exe', 'video/x-f4v' => 'f4v', 'audio/x-flac' => 'flac', 'video/x-flv' => 'flv', 'image/gif' => 'gif', 'application/gpg-keys' => 'gpg', 'application/x-gtar' => 'gtar', 'application/x-gzip' => 'gzip',
                'application/mac-binhex40' => 'hqx', 'application/mac-binhex' => 'hqx', 'application/x-binhex40' => 'hqx', 'application/x-mac-binhex40' => 'hqx', 'text/html' => 'html', 'image/x-icon' => 'ico', 'image/x-ico' => 'ico', 'image/vnd.microsoft.icon' => 'ico',
                'text/calendar' => 'ics', 'application/java-archive' => 'jar', 'application/x-java-application' => 'jar', 'application/x-jar' => 'jar', 'image/jp2' => 'jp2', 'video/mj2' => 'jp2', 'image/jpx' => 'jp2', 'image/jpm' => 'jp2', 'image/jpeg' => 'jpeg',
                'image/pjpeg' => 'jpeg', 'application/x-javascript' => 'js', 'application/json' => 'json', 'text/json' => 'json', 'application/vnd.google-earth.kml+xml' => 'kml', 'application/vnd.google-earth.kmz' => 'kmz', 'text/x-log' => 'log',
                'audio/x-m4a' => 'm4a', 'audio/mp4' => 'm4a', 'application/vnd.mpegurl' => 'm4u', 'audio/midi' => 'mid', 'application/vnd.mif' => 'mif', 'video/quicktime' => 'mov', 'video/x-sgi-movie' => 'movie', 'audio/mpeg' => 'mp3', 'audio/mpg' => 'mp3',
                'audio/mpeg3' => 'mp3', 'audio/mp3' => 'mp3', 'video/mp4' => 'mp4', 'video/mpeg' => 'mpeg', 'application/oda' => 'oda', 'audio/ogg' => 'ogg', 'video/ogg' => 'ogg', 'application/ogg' => 'ogg', 'font/otf' => 'otf', 'application/x-pkcs10' => 'p10',
                'application/pkcs10' => 'p10', 'application/x-pkcs12' => 'p12', 'application/x-pkcs7-signature' => 'p7a', 'application/pkcs7-mime' => 'p7c', 'application/x-pkcs7-mime' => 'p7c', 'application/x-pkcs7-certreqresp' => 'p7r',
                'application/pkcs7-signature' => 'p7s', 'application/pdf' => 'pdf', 'application/octet-stream' => 'pdf', 'application/x-x509-user-cert' => 'pem', 'application/x-pem-file' => 'pem', 'application/pgp' => 'pgp', 'application/x-httpd-php' => 'php',
                'application/php' => 'php', 'application/x-php' => 'php', 'text/php' => 'php', 'text/x-php' => 'php', 'application/x-httpd-php-source' => 'php', 'image/png' => 'png', 'image/x-png' => 'png', 'application/powerpoint' => 'ppt',
                'application/vnd.ms-powerpoint' => 'ppt', 'application/vnd.ms-office' => 'ppt', 'application/msword' => 'doc', 'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx', 'application/x-photoshop' => 'psd',
                'image/vnd.adobe.photoshop' => 'psd', 'audio/x-realaudio' => 'ra', 'audio/x-pn-realaudio' => 'ram', 'application/x-rar' => 'rar', 'application/rar' => 'rar', 'application/x-rar-compressed' => 'rar', 'audio/x-pn-realaudio-plugin' => 'rpm',
                'application/x-pkcs7' => 'rsa', 'text/rtf' => 'rtf', 'text/richtext' => 'rtx', 'video/vnd.rn-realvideo' => 'rv', 'application/x-stuffit' => 'sit', 'application/smil' => 'smil', 'text/srt' => 'srt', 'image/svg+xml' => 'svg',
                'application/x-shockwave-flash' => 'swf', 'application/x-tar' => 'tar', 'application/x-gzip-compressed' => 'tgz', 'image/tiff' => 'tiff', 'font/ttf' => 'ttf', 'text/plain' => 'txt', 'text/x-vcard' => 'vcf', 'application/videolan' => 'vlc',
                'text/vtt' => 'vtt', 'audio/x-wav' => 'wav', 'audio/wave' => 'wav', 'audio/wav' => 'wav', 'application/wbxml' => 'wbxml', 'video/webm' => 'webm', 'image/webp' => 'webp', 'audio/x-ms-wma' => 'wma', 'application/wmlc' => 'wmlc', 'video/x-ms-wmv' => 'wmv',
                'video/x-ms-asf' => 'wmv', 'font/woff' => 'woff', 'font/woff2' => 'woff2', 'application/xhtml+xml' => 'xhtml', 'application/excel' => 'xl', 'application/msexcel' => 'xls', 'application/x-msexcel' => 'xls', 'application/x-ms-excel' => 'xls',
                'application/x-excel' => 'xls', 'application/x-dos_ms_excel' => 'xls', 'application/xls' => 'xls', 'application/x-xls' => 'xls', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx', 'application/vnd.ms-excel' => 'xlsx',
                'application/xml' => 'xml', 'text/xml' => 'xml', 'text/xsl' => 'xsl', 'application/xspf+xml' => 'xspf', 'application/x-compress' => 'z', 'application/x-zip' => 'zip', 'application/zip' => 'zip', 'application/x-zip-compressed' => 'zip',
                'application/s-compressed' => 'zip', 'multipart/x-zip' => 'zip', 'text/x-scriptzsh' => 'zsh',];
        return $mime_map[$mime] ?? null;
    }


    //Downloads a requested file
    private function downloadFile($filePath)
    {
        // Check if the file exists
        if (!file_exists($filePath)) {
            error_log("noexist: $filePath");
            return false;
        }

        try {
            // Determine the file type and extension, then return the file and correct headers
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($filePath);
            $filename = basename($filePath);
            $extension = $this->getExtension($mime);

            $contentDisposition = 'attachment; filename="' . $filename . ($extension ? '.' . $extension : '') . '"';

            return [
                "Content-Type" => $mime,
                "Content-Disposition" => $contentDisposition,
                "file" => file_get_contents($filePath)
            ];
        } catch (Exception $e) {
            error_log($e);
            Logger::error($e, "Files/downloadFile");
            return false;
        }
    }

    //Downloads a requested folder
    private function downloadFolder($folderPath)
    {
        if (!is_dir($folderPath)) {
            return false;
        }

        try {
            $fileArray = explode($folderPath, "/");
            $fileName = end($fileArray);
            $zip = new ZipArchive();
            $zipPath = sys_get_temp_dir() . "/$fileName.zip";
            if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
                $files = scandir($folderPath);
                foreach ($files as $file) {
                    $filePath = (substr($folderPath, -1) === "/" ? $folderPath : $folderPath . "/") . $file;
                    $zip->addFile($filePath, $file);
                }
                $zip->close();
            } else {
                return false;
            }
        } catch (Exception $e) {
            Logger::error($e, "files/downloadFolder");
            return false;
        }

        return $this->downloadFile($zipPath);
    }

    //Helper function that deals with download input and output files
    private function download($userId, $jobId, $filePath, $type)
    {
        $filePath = str_replace("¬dot¬", ".", $filePath);
        if ($type !== "in" && $type !== "out") {
            error_log("Failed on type");
            return false;
        }

        if (str_contains($filePath, ".zip")) {
            $zipPosition = strpos($filePath, ".zip");
            $zipPath = substr($filePath, 0, $zipPosition + 4);
            $remainingPath = substr($filePath, $zipPosition + 5);

            $zipPath = __DIR__ . "/../usr/$type/$userId/$jobId/$zipPath";
            if (!file_exists($zipPath)) {
                $pathArray = explode(".", $zipPath);
                array_pop($pathArray);
                $zipPath = implode($pathArray, ".");
                if (!file_exists($zipPath)) {
                    error_log("Noexist: $zipPath");
                    return false;
                }
            }

            $zip = new ZipArchive();
            if ($zip->open($zipPath) === true) {
                if ($zip->locateName($remainingPath) !== false) {
                    $zip->extractTo(sys_get_temp_dir() . "/webslurm", $remainingPath);
                    $zip->close();
                } else {
                    error_log("noexist: $remainingPath");
                    return false;
                }

                return $this->downloadFile(sys_get_temp_dir() . "/webslurm/" . $remainingPath);
            } else {
                error_log("Ziperr");
                return false;
            }
        }

        $fullPath = __DIR__ . "/../usr/$type/$userId/$jobId/$filePath";

        error_log("FULLPATH: $fullPath");
        if (!file_exists($fullPath)) {
            $pathArray = explode(".", $fullPath);
            array_pop($pathArray);
            $fullPath = implode($pathArray, ".");
            error_log("Withoutext: $fullPath");
            if (!file_exists($fullPath)) {
                error_log("noexist");
                return false;
            }


        }

        return is_dir($fullPath)
            ? $this->downloadFolder($fullPath)
            : $this->downloadFile($fullPath);
    }

    //Recursively deletes a folder
    private function recursiveDelete($dir)
    {
        $files = glob($dir . "/*");
        foreach ($files as $file) {
            if (is_file($file))
                unlink($file);
            else if (is_dir($file))
                $this->recursiveDelete($dir . "/" . $file);
        }
    }

    //Recursively grabs the contents of a folder
    private function getFileTree($dir)
    {
        if (!file_exists($dir) || !is_dir($dir)) {
            return false;
        }

        $data = [];
        $files = array_filter(scandir($dir), function ($file) {
            return $file !== ".." && $file !== ".";
        });

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        foreach ($files as $file) {
            $path = "$dir/$file";
            $fileInfo = ['name' => $file, 'ext' => $this->getExtension($finfo->file($path))];
            if (is_dir($path)) {
                $fileInfo["contents"] = $this->getFileTree($path);
            } else if ($fileInfo["ext"] === "zip") {
                $zip = new ZipArchive;
                if ($zip->open($path) === TRUE) {
                    $tempDir = sys_get_temp_dir() . "/" . uniqid();
                    mkdir($tempDir);
                    $zip->extractTo($tempDir);
                    $zip->close();
                    $fileInfo['contents'] = $this->getFileTree($tempDir);
                    array_map('unlink', glob("$tempDir/*"));
                    $this->recursiveDelete($tempDir);
                }
            }
            $data[] = $fileInfo;
        }
        return $data;
    }

    private function checkUserAccess($userId, $jobId){
        $pdo = new PDO(DB_CONN);
        $getUsrIdStmt = $pdo->prepare("SELECT userId FROM jobs WHERE jobId = :jobId");
        $getUsrIdStmt->bindParam(":jobId", $jobId);
        if(!$getUsrIdStmt->execute()){
            throw new Error("Failed to get userId for job with ID $jobId: " . print_r($getUsrIdStmt->errorInfo(), true));
        }

        $createdBy = $getUsrIdStmt->fetchColumn();

        if($createdBy !== $userId){
            $getOrgIdStmt = $pdo->prepare("SELECT organisationId from organisationJobs WHERE jobId = :jobId");
            $getOrgIdStmt->bindParam(":jobId", $jobId);
            if(!$getOrgIdStmt->execute()){
                throw new Error("Failed to get organisation ID for job with ID $jobId: " . print_r($getOrgIdStmt->errorInfo(), true));
            }
            $organisationId = $getOrgIdStmt->fetchColumn();

            $Organisations = new Organisations();
            if($Organisations->_getUserRole($userId, $organisationId) === -1){
                return false;
            }
        }

        return $createdBy;
    }


    //===========================================================================//
    //=================================Routes===================================//
    //=========================================================================//

    //==============================Input Files===============================//

    //=========================Download Input File========================//
    //==========================Method: GET==============================//
    //=======Route: /api/files/input/download/{jobId}/{filePath}=======//
    public function downloadInputFile(Request $request, Response $response, array $args): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $jobId = $args["jobId"] ?? null;
        $filePath = $args["filePath"] ?? null;

        try {
            $createdById = $this->checkUserAccess($userId, $jobId);
            if(!$createdById){
                $response->getBody()->write("Unauthorized");
                return $response->withStatus(401);
            }

        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        if (!$jobId || !$filePath) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(500);
        }

        $fileInfo = $this->download($createdById, $jobId, urldecode($filePath), "in");

        if (!$fileInfo) {
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $response->getBody()->write($fileInfo["file"]);
        return $response->withAddedHeader("Content-Type", $fileInfo["Content-Type"])->withAddedHeader("Content-Disposition", $fileInfo["Content-Disposition"])->withStatus(200);
    }

    //=========================Download Input Tree========================//
    //==========================Method: GET==============================//
    //==============Route: /api/files/input/tree/{jobId}================//
    public function getInputTree(Request $request, Response $response, array $args): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $jobId = $args["jobId"] ?? null;

        try{
            $createdById = $this->checkUserAccess($userId, $jobId);
            if(!$createdById){
                $response->getBody()->write("Unauthorized");
                return $response->withStatus(401);
            }
        }catch(Exception $e){
            error_log("ERR: " .  $e);
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
        $folderPath = __DIR__ . "/../usr/in/$createdById/$jobId";
        $fileTree = $this->getFileTree($folderPath);
        if (!$fileTree) {
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $response->getBody()->write(json_encode($fileTree));
        return $response->withStatus(200);
    }

    //==============================Output Files===============================//

    //=========================Download Output File========================//
    //============================Method: GET============================//
    //=====Route: /api/files/output/download/{jobId}/{filePath}======//
    public function downloadOutputFile(Request $request, Response $response, array $args): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $jobId = $args["jobId"] ?? null;
        $filePath = $args["filePath"] ?? null;

        try{
            $createdById = $this->checkUserAccess($userId, $jobId);
        }catch(Exception $e){
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(401);
        }
        if (!$jobId || !$filePath) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(400);
        }

        $fileInfo = $this->download($createdById, $jobId, urldecode($filePath), "out");
        error_log(print_r($fileInfo, true));
        if (!$fileInfo) {
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $response->getBody()->write($fileInfo["file"]);
        return $response->withAddedHeader("Content-Type", $fileInfo["Content-Type"])->withAddedHeader("Content-Disposition", $fileInfo["Content-Disposition"])->withStatus(200);
    }


    //=========================Download Output Tree=======================//
    //============================Method: GET============================//
    //==============Route: /api/files/output/tree/{jobId}===============//
    public function getOutputTree(Request $request, Response $response, array $args): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $jobId = $args["jobId"] ?? null;

        try{
            $createdById = $this->checkUserAccess($userId, $jobId);
            if(!$createdById){
                $response->getBody()->write("Unauthorized");
                return $response->withStatus(401);
            }
        }catch(Exception $e){
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
        $folderPath = __DIR__ . "/../usr/out/$createdById/$jobId";
        $fileTree = $this->getFileTree($folderPath);
        if (!$fileTree) {
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
        $response->getBody()->write(json_encode($fileTree));
        return $response->withStatus(200);
    }

    //========================Generate File ID========================//
    //==========================Method: GET===========================//
    //=====================Route: /api/files/new=====================//
    public function generateFileId(Request $request, Response $response): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;

        $fileId = uniqid();
        $pdo = new PDO(DB_CONN);
        try {
            $fileIdStmt = $pdo->prepare("INSERT into FileIds (fileId, userId) VALUES (:fileId, :userId)");
            $fileIdStmt->bindParam(":fileId", $fileId);
            $fileIdStmt->bindParam(":userId", $userId);
            $ok = $fileIdStmt->execute();
            if (!$ok) {
                throw new Error("PDO Error: " . print_r($fileIdStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
        $response->getBody()->write(json_encode(["fileId" => $fileId]));
        return $response->withStatus(200);
    }

    //===========================Upload File==========================//
    //==========================Method: ANY===========================//
    //================Route: /api/files/upload[/{id}]================//
    public function handleFileUpload(Request $request, Response $response): Response
    {
        try {
            //Grab the token data
            $tokenData = $request->getAttribute("tokenData");
            $userId = $tokenData->userId;

            //Create the inDir if it doesn't exist
            $path = __DIR__ . "/../usr/in/$userId/";
            if (!file_exists($path)) {
                mkdir($path, 0775, true);
            }

            //Set the TUS upload directory to the users folder
            $this->server->setUploadDir($path);

            //The TUS server uses a different Request/Response interface to us, this converts.
            $psr17Factory = new Psr17Factory();
            $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

            //Convert the TUS server response to a PSR response and return to the user.
            $resp = $this->server->serve();
            return $psrHttpFactory->createResponse($resp);

        } catch (Exception $e) {
            Logger::error($e, "Jobs/handleFileUpload");
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }


}
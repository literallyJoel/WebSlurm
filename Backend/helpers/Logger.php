<?php
    class Logger{
  
        private static function setupFolders(){
            if(!file_exists(__DIR__ . "/../logs/")){
                mkdir(__DIR__ . "/../logs");
            }
            if(!file_exists(__DIR__ . "/../logs/log/")){
                mkdir(__DIR__ . "/../logs/log");
            }
            if(!file_exists(__DIR__ . "/../logs/error/")){
                mkdir(__DIR__ . "/../logs/error");
            }
            if(!file_exists(__DIR__ . "/../logs/debug/")){
                mkdir(__DIR__ . "/../logs/debug");
            }
            if(!file_exists(__DIR__ . "/../logs/warning/")){
                mkdir(__DIR__ . "/../logs/warning");
            }
            if(!file_exists(__DIR__ . "/../logs/complete/")){
                mkdir(__DIR__ . "/../logs/complete");
            }
            if(!file_exists(__DIR__ . "/../logs/telemetry/")){
                mkdir(__DIR__ . "/../logs/telemetry");
            }
        }

        private static function sendLog($log){
           //Logging for me
           $url = "https://logs.jdvivian.co.uk/api/logs/create";
           $data = ['appId' => "clu1e7xgx0000hxomsbgyb4pv", "log" => json_encode($log)];
           $ch = curl_init();
           curl_setopt($ch, CURLOPT_URL, $url);
           curl_setopt($ch, CURLOPT_POST, 1);
           curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
           curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
           $response = curl_exec($ch);
           self::setupFolders();
           $currentDate = date("Y-m-d");
           file_put_contents(__DIR__ . "/../logs/telemetry/" . $currentDate . '.log', json_encode($response) . "\n", FILE_APPEND);
            
        //    if($log["type"] === "debug"){
        //     mail("joel@jdvivian.co.uk", "Debug Log", print_r($log, true));
        //    }
          curl_close($ch);
        }

        
        public static function info($message, $route){
            $log = [
                "date" => (new DateTime())->format(DateTimeInterface::ATOM),
                "type" => "info",
                "route" => $route,
                "message" => $message
            ];
            error_log("[log][{$log["date"]}][{$log["route"]}] | {$log["message"]}");
          
           
            $currentDate = date("Y-m-d");;
            file_put_contents(__DIR__ . '/../logs/log/' . $currentDate . '.log', json_encode($log) . "\n", FILE_APPEND);
            file_put_contents(__DIR__ . "/../logs/complete/" . $currentDate . '.log', json_encode($log) . "\n", FILE_APPEND);
            self::sendLog($log);
        }


        public static function error($messageOrError, $route){
            $log = [
                "date" => (new DateTime())->format(DateTimeInterface::ATOM),
                "type" => "error",
                "route" => $route,
                "message" => $messageOrError instanceOf Error ? $messageOrError->getMessage() : $messageOrError,
            ];

            error_log("[error][{$log["date"]}][{$log["route"]}] | {$log["message"]}");
            self::setupFolders();
            $currentDate = date("Y-m-d");
            file_put_contents(__DIR__ . '/../logs/error/' . $currentDate . '.log', json_encode($log) . "\n", FILE_APPEND);
            file_put_contents(__DIR__ . "/../logs/complete/" . $currentDate . '.log', json_encode($log) . "\n", FILE_APPEND);

            self::sendLog($log);
        }

        public static function debug($message, $route){
            $log = [
                "date" => (new DateTime())->format(DateTimeInterface::ATOM),
                "type" => "debug",
                "route" => $route,
                "message" => $message
            ];

            self::setupFolders();
            $currentDate = date("Y-m-d");
            file_put_contents(__DIR__ . '/../logs/debug/' . $currentDate . ".log", json_encode($log) . "\n", FILE_APPEND);
            file_put_contents(__DIR__ . "/../logs/complete/" . $currentDate . '.log', json_encode($log) . "\n", FILE_APPEND);
            self::sendLog($log);
        }


        public static function warning($message, $route)
        {
            $log = [
                "date" => (new DateTime())->format(DateTimeInterface::ATOM),
                "type" => "warning",
                "route" => $route,
                "message" => $message
            ];
            error_log("[error][{$log["date"]}][{$log["route"]}] | {$log["message"]}");
            self::setupFolders();
            $currentDate = date("Y-m-d");
            file_put_contents(__DIR__ . '/../logs/warning/' . $currentDate . ".log", json_encode($log) . "\n", FILE_APPEND);
            file_put_contents(__DIR__ . "/../logs/complete/" . $currentDate . '.log', json_encode($log) . "\n", FILE_APPEND);
            self::sendLog($log);
        }
    }

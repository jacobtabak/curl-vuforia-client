<?php
/**
 * Created by PhpStorm.
 * User: jacob
 * Date: 8/12/14
 * Time: 3:49 PM
 */

class VuforiaClient {
    const JSON_CONTENT_TYPE = 'application/json';
    const ACCESS_KEY = '113ba2240641b609813e88861d200971bebbf1a4';
    const SECRET_KEY = '5c44c73ab289e51ab52b3bacf43d9db20dd8d830';
    const BASE_URL = 'https://vws.vuforia.com';
    const TARGETS_PATH = '/targets';
    const ID_INDEX = 0;
    const IMAGE_INDEX = 1;
    const WINE_COM_URL = 2;
    const VINTAGE = 3;
    const WINERY_NAME = 4;

    public function addTarget($row) {
        $ch = curl_init(self::BASE_URL . self::TARGETS_PATH);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $image = file_get_contents($row[self::IMAGE_INDEX]);
        $image_base64 = base64_encode($image);
        $post_data = array(
            'name' => $row[self::ID_INDEX],
            'width' => 32.0,
            'image' => $image_base64,
            'application_metadata' => $this->createMetadata($row),
            'active_flag' => 1
        );
        $body = json_encode($post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders('POST', self::TARGETS_PATH, self::JSON_CONTENT_TYPE, $body));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        if ($info['http_code'] !== 201) {
            print_r($row);
            print 'Failed to add target: ' . $response;
        } else {
            $id = json_decode($response)->target_id;
            print 'Successfully added target: ' . $id . "\n";
        }
    }
    
    public function deleteAllTargets() {
        $ch = curl_init(self::BASE_URL . self::TARGETS_PATH);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders('GET'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        if ($info['http_code'] !== 200) {
            die('Failed to list targets: ' . $response . "\n");
        }
        $targets = json_decode($response);
        foreach ($targets->results as $index => $id) {
            $path = self::TARGETS_PATH . "/" . $id;
            $ch = curl_init(self::BASE_URL . $path);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders('DELETE', $path));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            $info = curl_getinfo($ch);
            if ($info['http_code'] !== 200) {
                die('Failed to delete target: ' . $response . "\n");
            }
            print "Deleted target $index of " . count($targets->results);
        }
    }

    private function getHeaders($method, $path = self::TARGETS_PATH, $content_type = '', $body = '') {
        $headers = array();
        $date = new DateTime("now", new DateTimeZone("GMT"));
        $dateString = $date->format("D, d M Y H:i:s") . " GMT";
        $md5 = md5($body, false);
        $string_to_sign = $method . "\n" . $md5 . "\n" . $content_type . "\n" . $dateString . "\n" . $path;
        $signature = $this->hexToBase64(hash_hmac("sha1", $string_to_sign, self::SECRET_KEY));
        $headers[] = 'Authorization: VWS ' . self::ACCESS_KEY . ':' . $signature;
        $headers[] = 'Content-Type: ' . $content_type;
        $headers[] = 'Date: ' . $dateString;
        return $headers;
    }

    private function hexToBase64($hex){
        $return = "";
        foreach(str_split($hex, 2) as $pair){
            $return .= chr(hexdec($pair));
        }
        return base64_encode($return);
    }

    private function createMetadata($row) {
        $metadata = array(
            'wine_id' => $row[self::ID_INDEX],
            'image_url' => $row[self::IMAGE_INDEX],
            'wine_com_url' => $row[self::WINE_COM_URL],
            'vintage' => $row[self::VINTAGE],
            'winery_name' => $row[self::WINERY_NAME]
        );
        return base64_encode(json_encode($metadata));
    }
} 
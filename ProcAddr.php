<?php

class ProcAddr
{
    public $kenAllRomaFile = 'KEN_ALL_ROME.CSV';

    public $fromEnc = 'SJIS,UTF-8,EUC-JP';

    public $toEnc = 'UTF-8';

    public function readKenAll()
    {
        $indexes = [];
        $fp = fopen($this->kenAllRomaFile, "r");
        while (($line = fgets($fp)) !== FALSE) {
            $line = mb_convert_encoding(trim($line), $this->toEnc, $this->fromEnc);
            if (!$line) {
                continue;
            }
            if (!preg_match('/東京都|神奈川県|埼玉県|千葉県/u', $line)) {
                continue;
            }
            if (!$data = $this->parseLine($line)) {
                continue;
            }
            $prefEn = implode("-", explode(' ', $data['pref_en']));
            if (!isset($indexes[$data['pref']])) {
                $indexes[$data['pref']] = $prefEn;
            } elseif ($indexes[$data['pref']] !== $prefEn) {
                throw new Exception('Difference data.');
            }
            $arr = explode(' ', $data['city_en']);
            $bufStr = '';
            $cityEn = [];
            foreach ($arr as $val) {
                //echo $val."\n";
                if ($val === 'MU') {
                    $val = 'MURA';
                }
                if ($val === 'MAC' || $val === 'MACH') {
                    $val = 'MACHI';
                }
                if ($bufStr && in_array($val, ['SHI', 'KU', 'CHO', 'SON', 'MURA', 'GUN', 'MACHI'])) {
                    $cityEn[] = $bufStr.'-'.$val;
                    $bufStr = '';
                    continue;
                }
                if ($bufStr) {
                    $cityEn[] = $bufStr;
                }
                $bufStr = $val;
            }
            if ($bufStr) {
                $cityEn[] = $bufStr;
            }
            $city = preg_split('/[　]/u', $data['city']);
            if (count($city) !== count($cityEn)) {
                echo sprintf("%s\t%s\n"
                    , implode(', ', $city)
                    , implode(', ', $cityEn)
                );
                throw new Exception('Different numbers');
            }
            foreach ($city as $key => $val) {
                if (!isset($indexes[$val])) {
                    $indexes[$val] = $cityEn[$key];
                } elseif ($indexes[$val] !== $cityEn[$key]) {
                    if (in_array($val, [
                        '南牧村', '池田町', '朝日町', '明和町', '愛知郡', '三島郡', '川西町',
                        '海部郡', '松前町', '広川町', '鳩山町', '栄町', '日の出町', '寒川町'
                    ])) {
                        continue;
                    }
                    var_dump($indexes[$val]);
                    print_r($city);
                    print_r($cityEn);
                    throw new Exception('Different');
                }
            }
            $addr = preg_split('/　/u', $data['addr']);
            $addrEn = explode(' ', $data['addr_en']);
            if (count($addr) !== count($addrEn)) {
                if (preg_match('/西新宿/u', $addr[0])) {
                    continue;
                }
                echo sprintf("%s\t%s\n"
                    , $data['addr']
                    , $data['addr_en']
                );
                $addr = [implode('', $addr)];
                $addrEn = [implode(' ', $addrEn)];
            }
            foreach ($addr as $key => $val) {
                if (!isset($indexes[$val])) {
                    $indexes[$val] = $addrEn[$key];
                } elseif ($indexes[$val] !== $addrEn[$key]) {
                }
            }

            //print_r($data);
            //break;
            //echo $line."\n";
        }
        fclose($fp);
        print_r($indexes);
    }

    public function parseLine($line)
    {
        if (preg_match_all('/"([^"]*)"/u', $line, $reg)) {
            if (count($reg[1]) != 7) {
                print_r($reg[1]);
                throw new Exception("Unexpected data.");
            }
            $arr = [
                'code'    => trim($reg[1][0]),
                'pref'    => trim($reg[1][1]),
                'city'    => trim($reg[1][2]),
                'addr'    => trim($reg[1][3]),
                'pref_en' => trim($reg[1][4]),
                'city_en' => trim($reg[1][5]),
                'addr_en' => trim($reg[1][6]),
            ];
            return $arr;
        }
    }
}




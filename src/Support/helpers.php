<?php

use Carbon\Carbon;
use Myzx\PhpHelper\Support\HigherOrderTapProxy;

if (!function_exists('tap')) {
    /**
     * Call the given Closure with the given value then return the value.
     *
     * @param mixed $value
     * @param callable|null $callback
     * @return mixed
     */
    function tap($value, callable $callback = null)
    {
        if (is_null($callback)) {
            return new HigherOrderTapProxy($value);
        }

        $callback($value);

        return $value;
    }
}

if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param mixed $value
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

if (!function_exists('blank')) {
    /**
     * Determine if the given value is "blank".
     *
     * @param mixed $value
     * @return bool
     */
    function blank($value): bool
    {
        if (is_null($value)) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_numeric($value) || is_bool($value)) {
            return false;
        }

        if ($value instanceof Countable) {
            return count($value) === 0;
        }

        return empty($value);
    }
}

if (!function_exists('filled')) {
    /**
     * Determine if a value is "filled".
     *
     * @param mixed $value
     * @return bool
     */
    function filled($value): bool
    {
        return !blank($value);
    }
}

if (!function_exists('windows_os')) {
    /**
     * Determine whether the current environment is Windows based.
     *
     * @return bool
     */
    function windows_os(): bool
    {
        return strtolower(substr(PHP_OS, 0, 3)) === 'win';
    }
}

if (!function_exists('object_get')) {
    /**
     * Get an item from an object using "dot" notation.
     *
     * @param object $object
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function object_get(object $object, $key, $default = null)
    {
        if (is_null($key) || trim($key) == '') {
            return $object;
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_object($object) || !isset($object->{$segment})) {
                return value($default);
            }

            $object = $object->{$segment};
        }

        return $object;
    }
}

if (!function_exists('build_mysql_distance_field')) {
    /**
     * 生成计算位置字段
     *
     * @param float $longitude
     * @param float $latitude
     * @param string $lng_name
     * @param string $lat_name
     * @param string $as_name
     * @return string
     */
    function build_mysql_distance_field(float $longitude, float $latitude, string $lng_name = 'longitude', string $lat_name = 'latitude', string $as_name = 'distance'): string
    {
        $sql = "ROUND(6378.138*2*ASIN(SQRT(POW(SIN(({$latitude}*PI()/180-{$lat_name}*PI()/180)/2),2)+COS({$latitude}*PI()/180)*COS({$lat_name}*PI()/180)*POW(SIN(({$longitude}*PI()/180-{$lng_name}*PI()/180)/2),2)))*1000)";
        if ($as_name) {
            $sql .= " AS {$as_name}";
        }

        return $sql;
    }
}

if (!function_exists('get_class_const_list')) {
    /**
     * 获取常量列表
     *
     * @param string $class
     * @return array|bool
     */
    function get_class_const_list(string $class)
    {
        try {
            $ref = new \ReflectionClass($class);

            return $ref->getConstants();
        } catch (\ReflectionException $e) {
        }

        return false;
    }
}

if (!function_exists('get_const_value')) {
    /**
     * 获取常量列表
     *
     * @param string $class
     * @param string $name
     * @return mixed
     */
    function get_const_value(string $class, string $name)
    {
        try {
            $ref = new \ReflectionClass($class);
            if (!$ref->hasConstant($name)) {
                return null;
            }

            return $ref->getConstant($name);
        } catch (\ReflectionException $e) {
        }

        return false;
    }
}

if (!function_exists('const_exist')) {
    /**
     * 类常量是否存在
     *
     * @param string $class
     * @param string $name
     * @return bool
     */
    function const_exist(string $class, string $name): bool
    {
        try {
            $ref = new \ReflectionClass($class);

            return $ref->hasConstant($name);
        } catch (\ReflectionException $e) {
        }

        return false;
    }
}

if (!function_exists('now')) {
    /**
     * 获取当前时间实例
     *
     * @param DateTimeZone|string|null $tz $tz
     * @return Carbon
     */
    function now($tz = null): Carbon
    {
        return Carbon::now($tz);
    }
}

if (!function_exists('phoneHide')) {
    /**
     * 手机号脱敏
     * @param $phone
     * @return string
     */
    function phoneHide($phone): string
    {
        if (empty($phone)) {
            return '';
        }
        return substr_replace($phone, '****', 3, -4);
    }
}

if (!function_exists('idcardHide')) {
    /**
     * 身份证号脱敏
     * @param $idcard
     * @return string
     */
    function idcardHide($idcard): string
    {
        if (empty($idcard)) {
            return '';
        }

        $idcardLen = strlen($idcard);
        if ($idcardLen == 18) { // 按照身份证号码脱敏
            return substr_replace($idcard, '***********', 3, -4);
        } else { // 其他证件脱敏
            return substr_replace($idcard, str_pad('*', $idcardLen - 2, '*'), 1, -1);
        }
    }
}

if (!function_exists('getGenderByIdcard')) {
    /**
     * 通过身份证号，获取就诊人性别
     * @param $idcard
     * @return int
     */
    function getGenderByIdcard($idcard): int
    {
        //0-未知； 1-男； 2-女；
        if (empty($idcard) || strlen($idcard) != 18) {
            return 0;
        }
        $id = strtoupper($idcard);
        $regx = "/(^\d{17}([0-9]|X)$)/";
        if (!preg_match($regx, $id)) {
            return 0;
        }

        $div = substr($idcard, -2, 1) % 2;
        if ($div == 0) {//奇数为男，偶数为女
            return 2;//女
        }
        return 1;//男
    }
}

if (!function_exists('analysisIdcard')) {
    /**
     * 解析身份证号
     * @param string $idcard 身份证号
     * @return array
     */
    function analysisIdcard(string $idcard): array
    {
        $result = [
            'valid' => false,
            'age' => '',
            'month' => '',
            'sex' => '',
            'sex_desc' => '',
            'birthday' => '',
            'msg' => '',
        ];
        $id = strtoupper($idcard);
        $regx = "/(^\d{17}([0-9]|X)$)/";
        $arr_split = array();
        if (!preg_match($regx, $id)) {
            $result['msg'] = '身份证号码格式不正确';
            return $result;
        }

        $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";
        @preg_match($regx, $id, $arr_split);
        $dtm_birth = $arr_split[2] . '/' . $arr_split[3] . '/' . $arr_split[4];
        if (!strtotime($dtm_birth)) { //检查生日日期是否正确
            $result['msg'] = '身份证号码出生日期不正确';
            return $result;
        }
        $obj = Carbon::createFromDate($arr_split[2] . '-' . $arr_split[3] . '-' . $arr_split[4]);
        //检验18位身份证的校验码是否正确。
        //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
        $arr_int = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
        $arr_ch = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
        $sign = 0;
        for ($i = 0; $i < 17; $i++) {
            $b = (int)$id[$i];
            $w = $arr_int[$i];
            $sign += $b * $w;
        }
        $n = $sign % 11;
        $val_num = $arr_ch[$n];
        if ($val_num != substr($id, 17, 1)) {
            $result['msg'] = '身份证号码不正确';
            return $result;
        }

        $result['valid'] = true;
        $result['age'] = $obj->age;
        $result['month'] = $obj->diffInMonths();
        $result['birthday'] = $obj->toDateString();
        $result['sex'] = ((int)substr($id, -2, 1) % 2) == 1 ? 1 : 2; // 1男、2女
        $result['sex_desc'] = $result['sex'] == 1 ? '男' : '女';

        return $result;
    }
}
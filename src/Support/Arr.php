<?php

namespace Myzx\PhpHelper\Support;

final class Arr
{
    /**
     * 给定值是否可由数组访问
     *
     * @param mixed $value
     * @return bool
     */
    public static function accessible($value): bool
    {
        return is_array($value) || $value instanceof \ArrayAccess;
    }

    /**
     * 给定值是否为关联数组
     *
     * @param array $arr 数组
     * @return bool
     */
    public static function isAssoc(array $arr): bool
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * 确定给定的键是否存在于提供的数组中
     *
     * @param \ArrayAccess|array $array
     * @param string|int $key
     * @return bool
     */
    public static function exists($array, $key): bool
    {
        if ($array instanceof \ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
    }

    /**
     * 如果元素不存在，则使用“点”表示法将其添加到数组中
     *
     * @param array $array
     * @param string $key
     * @param mixed $value
     * @return array
     */
    public static function add(array $array, string $key, $value): array
    {
        if (is_null(static::get($array, $key))) {
            static::set($array, $key, $value);
        }

        return $array;
    }

    /**
     * 支持使用“点”表示法从数组中获取项
     *
     * @param \ArrayAccess|array $array
     * @param string|int $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($array, $key, $default = null)
    {
        if (!self::accessible($array)) {
            return value($default);
        }

        if (is_null($key)) {
            return $array;
        }

        if (self::exists($array, $key)) {
            return $array[$key];
        }

        if (strpos($key, '.') === false) {
            return isset($array[$key]) ? $array[$key] : value($default);
        }

        foreach (explode('.', $key) as $segment) {
            if (self::accessible($array) && self::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return value($default);
            }
        }

        return $array;
    }

    /**
     * 支持使用“点”表示法检查数组中是否存在一个或多个项
     *
     * @param \ArrayAccess|array $array
     * @param string|array $keys
     * @return bool
     */
    public static function has($array, $keys): bool
    {
        $keys = (array)$keys;

        if (!$array || $keys === []) {
            return false;
        }

        foreach ($keys as $key) {
            $subKeyArray = $array;

            if (self::exists($array, $key)) {
                continue;
            }

            foreach (explode('.', $key) as $segment) {
                if (self::accessible($subKeyArray) && self::exists($subKeyArray, $segment)) {
                    $subKeyArray = $subKeyArray[$segment];
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 支持使用“点”表示法将数组项设置为给定值
     * 如果没有给方法指定键，整个数组将被替换
     *
     * @param array $array
     * @param string $key
     * @param mixed $value
     * @return array
     */
    public static function set(array &$array, $key, $value): array
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $key = array_shift($keys);
        if (isset($array[$key]) && is_array($array[$key]) && is_array($value)) {
            $array[$key] = array_merge($array[$key], $value);
        } else {
            $array[$key] = $value;
        }

        return $array;
    }

    /**
     * 使用“点”表示法从给定数组中删除一个或多个数组项
     *
     * @param array $array
     * @param array|string $keys
     * @return void
     */
    public static function forget(array &$array, $keys)
    {
        $original = &$array;

        $keys = (array)$keys;

        if (count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            if (static::exists($array, $key)) {
                unset($array[$key]);

                continue;
            }

            $parts = explode('.', $key);

            // clean up before each pass
            $array = &$original;

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }
    }

    /**
     * 获取除指定的键数组以外的所有给定数组
     *
     * @param array $array
     * @param array|string $keys
     * @return array
     */
    public static function except(array $array, $keys): array
    {
        static::forget($array, $keys);

        return $array;
    }

    /**
     * 如果给定的值不是数组且不是null，将其包装在一个数组中
     *
     * @param mixed $value
     * @return array
     */
    public static function wrap($value): array
    {
        if (is_null($value)) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    /**
     * 将数组使用点展平多维关联数组
     *
     * @param array $array
     * @param string $prepend
     * @return array
     */
    public static function dot(array $array, string $prepend = ''): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $results = array_merge($results, self::dot($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }

        return $results;
    }

    /**
     * 交叉连接给定的数组，返回所有可能的排列
     * 使用用例：产品规格
     *
     * @param array ...$arrays
     * @return array
     */
    public static function crossJoin(...$arrays): array
    {
        $results = [[]];

        foreach ($arrays as $index => $array) {
            $append = [];

            foreach ($results as $product) {
                foreach ($array as $item) {
                    $product[$index] = $item;

                    $append[] = $product;
                }
            }

            $results = $append;
        }

        return $results;
    }

    /**
     * 把一个数组分成两个数组。一个带有键，另一个带有值
     *
     * @param array $array
     * @return array
     */
    public static function divide(array $array): array
    {
        return [array_keys($array), array_values($array)];
    }

    /**
     * 从数组里面获取指定的数据
     *
     * @param array $data
     * @param array $keys
     * @return array
     */
    public static function only(array $data, array $keys): array
    {
        return array_intersect_key($data, array_flip((array)$keys));
        //		$result = [];
        //		foreach($keys as $key){
        //			if(isset($data[$key])){
        //				$result[$key] = $data[$key];
        //			}
        //		}
        //
        //		return $result;
    }

    /**
     * 返回数组中通过给定真值测试的第一个元素
     *
     * @param array|iterable $array
     * @param callable|null $callback
     * @param mixed $default
     * @return mixed
     */
    public static function first($array, callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            if (empty($array)) {
                return value($default);
            }

            foreach ($array as $item) {
                return $item;
            }
        }

        foreach ($array as $key => $value) {
            if (call_user_func($callback, $value, $key)) {
                return $value;
            }
        }

        return value($default);
    }

    /**
     * 返回数组中通过给定真值测试的最后一个元素
     *
     * @param array $array
     * @param callable|null $callback
     * @param mixed $default
     * @return mixed
     */
    public static function last(array $array, callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            return empty($array) ? value($default) : end($array);
        }

        return self::first(array_reverse($array, true), $callback, $default);
    }

    /**
     * 将多维数组展平为单个级别
     *
     * @param array $array
     * @param int $depth
     * @return array
     */
    public static function flatten(array $array, $depth = INF): array
    {
        $result = [];

        foreach ($array as $item) {
            if (!is_array($item)) {
                $result[] = $item;
            } else {
                $values = $depth === 1 ? array_values($item) : self::flatten($item, $depth - 1);

                foreach ($values as $value) {
                    $result[] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * 检测数组所有元素是否都符合指定条件
     *
     * @param array|iterable $array
     * @param string|callable $callback
     * @return bool
     */
    public static function every($array, $callback): bool
    {
        foreach ($array as $k => $v) {
            if (!$callback($v, $k)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 将项目推送到数组的开头
     *
     * @param array $array
     * @param mixed $value
     * @param mixed $key
     * @return array
     */
    public static function prepend(array $array, $value, $key = null): array
    {
        if (is_null($key)) {
            array_unshift($array, $value);
        } else {
            $array = [$key => $value] + $array;
        }

        return $array;
    }

    /**
     * 从数组中获取一个值，并将其移除
     *
     * @param array $array
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function pull(array &$array, string $key, $default = null)
    {
        $value = static::get($array, $key, $default);

        static::forget($array, $key);

        return $value;
    }

    /**
     * 从数组中获取一个或指定数量的随机值
     *
     * @param array $array
     * @param int|null $number
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public static function random(array $array, int $number = null)
    {
        $requested = is_null($number) ? 1 : $number;

        $count = count($array);

        if ($requested > $count) {
            throw new \InvalidArgumentException(
                "You requested {$requested} items, but there are only {$count} items available."
            );
        }

        if (is_null($number)) {
            return $array[array_rand($array)];
        }

        if ((int)$number === 0) {
            return [];
        }

        $keys = array_rand($array, $number);

        $results = [];

        foreach ((array)$keys as $key) {
            $results[] = $array[$key];
        }

        return $results;
    }

    /**
     * 打乱给定数组并返回结果
     *
     * @param array $array
     * @param int|null $seed
     * @return array
     */
    public static function shuffle(array $array, int $seed = null): array
    {
        if (is_null($seed)) {
            shuffle($array);
        } else {
            mt_srand($seed);
            shuffle($array);
            mt_srand();
        }

        return $array;
    }

    /**
     * 根据字段规则判断给定的数组是否满足条件
     *
     * @param mixed $array
     * @param array $condition
     * @param bool $any
     * @return bool
     */
    public static function where($array, array $condition, bool $any = false): bool
    {
        if (self::isAssoc($condition)) {
            $temp = [];
            foreach ($condition as $key => $value) {
                $temp[] = [$key, '=', $value];
            }
            $condition = $temp;
        }

        foreach ($condition as $item) {
            [$field, $operator, $value] = $item;

            if (strpos($field, '.')) {
                $result = self::get($array, $field);
            } else {
                $result = $array[$field] ?? null;
            }

            switch (strtolower($operator)) {
                case '===':
                    $flag = $result === $value;
                    break;
                case '!==':
                    $flag = $result !== $value;
                    break;
                case '!=':
                case '<>':
                    $flag = $result != $value;
                    break;
                case '>':
                    $flag = $result > $value;
                    break;
                case '>=':
                    $flag = $result >= $value;
                    break;
                case '<':
                    $flag = $result < $value;
                    break;
                case '<=':
                    $flag = $result <= $value;
                    break;
                case 'like':
                    $flag = is_string($result) && false !== strpos($result, $value);
                    break;
                case 'not like':
                    $flag = is_string($result) && false === strpos($result, $value);
                    break;
                case 'in':
                    $flag = is_scalar($result) && in_array($result, $value, true);
                    break;
                case 'not in':
                    $flag = is_scalar($result) && !in_array($result, $value, true);
                    break;
                case 'between':
                    [$min, $max] = is_string($value) ? explode(',', $value) : $value;
                    $flag = is_scalar($result) && $result >= $min && $result <= $max;
                    break;
                case 'not between':
                    [$min, $max] = is_string($value) ? explode(',', $value) : $value;
                    $flag = is_scalar($result) && $result > $max || $result < $min;
                    break;
                case '==':
                case '=':
                default:
                    $flag = $result == $value;
            }

            if ($any && $flag) {
                return true;
            } elseif (!$any && !$flag) {
                return false;
            }
        }

        return true;
    }

    /**
     * 除去数组中的空值和和附加键名
     *
     * @param array $params 要去除的数组
     * @param array $filter 要额外过滤的数据
     * @return array
     */
    public static function filter(array &$params, array $filter = ["sign", "sign_type"]): array
    {
        foreach ($params as $key => $val) {
            if ($val == "" || (is_array($val) && count($val) == 0)) {
                unset ($params [$key]);
            } else {
                $len = count($filter);
                for ($i = 0; $i < $len; $i++) {
                    if ($key == $filter [$i]) {
                        unset ($params [$key]);
                        array_splice($filter, $i, 1);
                        break;
                    }
                }
            }
        }

        return $params;
    }

    /**
     * 不区分大小写的in_array实现
     *
     * @param $value
     * @param $array
     * @return bool
     */
    public static function in($value, $array): bool
    {
        return in_array(strtolower($value), array_map('strtolower', $array));
    }

    /**
     * 对数组排序
     *
     * @param array $array
     * @return array
     */
    public static function sort(array &$array): array
    {
        if (static::isAssoc($array)) {
            ksort($array);
        } else {
            sort($array);
        }
        reset($array);

        return $array;
    }

    /**
     * 按键和值对数组进行递归排序
     *
     * @param array $array
     * @return array
     */
    public static function sortRecursive(array $array): array
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = static::sortRecursive($value);
            }
        }

        if (static::isAssoc($array)) {
            ksort($array);
        } else {
            sort($array);
        }

        return $array;
    }

    /**
     * 将数组转换为查询字符串
     *
     * @param array $array
     * @return string
     */
    public static function query(array $array): string
    {
        return http_build_query($array, null, '&', PHP_QUERY_RFC3986);
    }

    /**
     * 从数组中提取指定的值数组
     *
     * @param array $array
     * @param string $column
     * @param string|null $index_key
     * @return array
     */
    public static function column(array $array, $column, string $index_key = null): array
    {
        $result = [];

        foreach ($array as $row) {
            $key = $value = null;
            $keySet = $valueSet = false;
            if ($index_key !== null && array_key_exists($index_key, $row)) {
                $keySet = true;
                $key = (string)$row[$index_key];
            }
            if ($column === null) {
                $valueSet = true;
                $value = $row;
            } elseif (is_array($row) && array_key_exists($column, $row)) {
                $valueSet = true;
                $value = $row[$column];
            }
            if ($valueSet) {
                if ($keySet) {
                    $result[$key] = $value;
                } else {
                    $result[] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * 解包数组
     *
     * @param array $array
     * @param string|array $keys
     * @return array
     */
    public static function uncombine(array $array, $keys = null): array
    {
        $result = [];

        if ($keys) {
            $keys = is_array($keys) ? $keys : explode(',', $keys);
        } else {
            $keys = array_keys(current($array));
        }

        foreach ($keys as $index => $key) {
            $result[$index] = [];
        }

        foreach ($array as $item) {
            foreach ($keys as $index => $key) {
                $result[$index][] = $item[$key] ?? null;
            }
        }

        return $result;
    }

    /**
     * 数组去重 - 二维数组
     *
     * @param array $array
     * @param string $key
     * @return array
     * @link https://www.php.net/manual/zh/function.array-unique.php#116302
     */
    public static function uniqueMulti(array $array, string $key): array
    {
        $i = 0;
        $temp_array = [];
        $key_array = [];

        foreach ($array as $val) {
            if (!in_array($val[$key], $key_array)) {
                $key_array[$i] = $val[$key];
                $temp_array[$i] = $val;
            }
            $i++;
        }

        return $temp_array;
    }

    /**
     * 无极限分类
     *
     * @param array $list 数据源
     * @param callable|null $itemHandler 额外处理回调函数
     * @param int $pid 父id
     * @param array $options
     * @return array
     */
    public static function tree(array $list, callable $itemHandler = null, int $pid = 0, array $options = []): array
    {
        $options = array_merge([
            'id' => 'id', // 要检索的ID键名
            'parent' => 'pid', // 要检索的parent键名
            'child' => 'child', // 要存放的子结果集
            'with_unknown' => false, // 是否把未知的上级当成1级返回
        ], $options);

        if (is_null($itemHandler)) {
            $itemHandler = function ($level, &$value) {
            };
        }

        $level = 0;
        $handler = function (array &$list, $pid) use (&$handler, &$level, &$itemHandler, &$options) {
            $level++;
            $idKey = $options['id'];
            $parentKey = $options['parent'];
            $childKey = $options['child'];

            $result = [];
            foreach ($list as $key => $value) {
                if ($value[$parentKey] == $pid) {
                    unset ($list[$key]);

                    $flag = $itemHandler($level, $value);

                    $childList = $handler($list, $value[$idKey]);
                    if (!empty($childList)) {
                        $value[$childKey] = $childList;
                    }

                    if ($flag !== false) {
                        $result[] = $value;
                        reset($list);
                    }
                }
            }
            $level--;

            return $result;
        };

        $result = $handler($list, $pid);

        // 是否把未知的上级当成1级返回
        if (!empty($list) && $options['with_unknown']) {
            $level = 1;
            foreach ($list as &$value) {
                $itemHandler($level, $value);
            }
            unset($value);

            $result = array_merge($result, array_values($list));
        }

        return $result;
    }

    /**
     * 解除Tree结构数据
     *
     * @param array $list
     * @param string $child
     * @return array
     */
    public static function treeToList(array $list, string $child = 'child'): array
    {
        $handler = function ($list, $child) use (&$handler) {
            $result = [];
            foreach ($list as $key => &$val) {
                $result[] = &$val;
                unset($list[$key]);
                if (isset($val[$child])) {
                    $result = array_merge($result, $handler($val[$child], $child));
                    unset($val[$child]);
                }
            }
            unset($val);

            return $result;
        };

        return $handler($list, $child);
    }

    /**
     * 转换指定数组里面的 key
     *
     * @param array $arr
     * @param array $keyMaps
     * @return array
     */
    public static function transformKeys(array $arr, array $keyMaps): array
    {
        foreach ($keyMaps as $oldKey => $newKey) {
            if (!array_key_exists($oldKey, $arr)) continue;

            if (is_callable($newKey)) {
                [$newKey, $value] = call_user_func($newKey, $arr[$oldKey], $oldKey, $arr);
                $arr[$newKey] = $value;
            } else {
                $arr[$newKey] = $arr[$oldKey];
            }
            unset($arr[$oldKey]);
        }

        return $arr;
    }

    /**
     * 合并默认数据（要合并的数组只会保留$default中所包含的键名）
     *
     * @param array $default
     * @param array $data
     * @return array
     * @link https://www.php.net/manual/zh/function.array-intersect-key.php#80227
     */
    public static function mergeDefault(array $default, array $data): array
    {
        $intersect = array_intersect_key($data, $default); //Get data for which a default exists
        $diff = array_diff_key($default, $data); //Get defaults which are not present in data

        return $diff + $intersect; //Arrays have different keys, return the union of the two
    }

    /**
     * 解析字符串为数组
     *
     * @param string $string
     * @return array
     */
    public static function parse(string $string): array
    {
        $array = preg_split('/[,;\r\n]+/', trim($string, ",;\r\n"));

        if (strpos($string, ':')) {
            $value = [];
            foreach ($array as $val) {
                $val = explode(':', $val);
                if (isset($val[1]) && $val[0] !== '') {
                    $value[$val[0]] = $val[1];
                } else {
                    $value[] = $val[0];
                }
            }
        } else {
            $value = $array;
        }

        return $value;
    }

    /**
     * 数组解析为字符串
     *
     * @param array $array
     * @return string
     */
    public static function toString(array $array): string
    {
        $result = '';

        if (self::isAssoc($array)) {
            foreach ($array as $key => $val) {
                $result .= "{$key}:$val\n";
            }
        } else {
            $result = implode("\n", $array);
        }

        return $result;
    }
}
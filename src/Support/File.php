<?php

namespace Myzx\PhpHelper\Support;

/**
 * 文件操作
 */
class File
{

    /**
     * 创建多级目录
     * @param string $dir
     * @param int $mode
     * @return boolean
     */
    public function create_dir(string $dir, int $mode = 0777): bool
    {
        return is_dir($dir) or ($this->create_dir(dirname($dir)) and mkdir($dir, $mode));
    }

    /**
     * 创建指定路径下的指定文件
     * @param string $path (需要包含文件名和后缀)
     * @param boolean $over_write 是否覆盖文件
     * @param int|null $time 设置时间。默认是当前系统时间
     * @param int|null $atime 设置访问时间。默认是当前系统时间
     * @return boolean
     */
    public function create_file(string $path, bool $over_write = FALSE, int $time = NULL, int $atime = NULL): bool
    {
        $path = $this->dir_replace($path);
        $time = empty($time) ? time() : $time;
        $atime = empty($atime) ? time() : $atime;
        if (file_exists($path) && $over_write) {
            $this->unlink_file($path);
        }
        $aimDir = dirname($path);
        $this->create_dir($aimDir);
        return touch($path, $time, $atime);
    }

    /**
     * 关闭文件操作
     * @param $stream
     * @return bool
     */
    public function close($stream): bool
    {
        return fclose($stream);
    }

    /**
     * 读取文件操作
     * @param string $file
     * @return boolean
     */
    public function read_file(string $file): bool
    {
        return @file_get_contents($file);
    }

    /**
     * 确定服务器的最大上传限制（字节数）
     * @return int 服务器允许的最大上传字节数
     */
    public function allow_upload_size(): int
    {
        return trim(ini_get('upload_max_filesize'));
    }

    /**
     * 字节格式化 把字节数格式为 B K M G T P E Z Y 描述的大小
     * @param int $size 大小
     * @param int $dec 显示类型
     * @return int|string
     */
    public function byte_format(int $size, int $dec = 2): int|string
    {
        $a = array("B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB");
        $pos = 0;
        while ($size >= 1024) {
            $size /= 1024;
            $pos++;
        }
        return round($size, $dec) . " " . $a[$pos];
    }

    /**
     * 删除非空目录
     * 说明:只能删除非系统和特定权限的文件,否则会出现错误
     * @param string $dir_path 目录路径
     * @param boolean $is_all 是否删除所有
     * @return bool
     */
    public function remove_dir(string $dir_path, bool $is_all = FALSE): bool
    {
        $dirName = $this->dir_replace($dir_path);
        $handle = @opendir($dirName);
        while (($file = @readdir($handle)) !== FALSE) {
            if ($file != '.' && $file != '..') {
                $dir = $dirName . '/' . $file;
                if ($is_all) {
                    is_dir($dir) ? $this->remove_dir($dir) : $this->unlink_file($dir);
                } else {
                    if (is_file($dir)) {
                        $this->unlink_file($dir);
                    }
                }
            }
        }
        closedir($handle);
        return @rmdir($dirName);
    }

    /**
     * 获取完整文件名
     * @param string $file_path 路径
     * @return string
     */
    public function get_basename(string $file_path): string
    {
        $file_path = $this->dir_replace($file_path);
        return basename(str_replace('\\', '/', $file_path));
        //return pathinfo($file_path,PATHINFO_BASENAME);
    }

    /**
     * 获取文件后缀名
     * @param string $file 文件路径
     * @return string
     */
    public function get_ext(string $file): string
    {
        $file = $this->dir_replace($file);
        //return strtolower(substr(strrchr(basename($file), '.'),1));
        //return end(explode(".",$filename ));
        //return strtolower(trim(array_pop(explode('.', $file))));//取得后缀
        //return preg_replace('/.*\.(.*[^\.].*)*/iU','\\1',$file);
        return pathinfo($file, PATHINFO_EXTENSION);
    }

    /**
     * 取得指定目录名称
     * @param string $path 文件路径
     * @param int $num 需要返回以上级目录的数
     * @return string
     */
    public function father_dir(string $path, int $num = 1): string
    {
        $path = $this->dir_replace($path);
        $arr = explode('/', $path);
        if ($num == 0 || count($arr) < $num) return pathinfo($path, PATHINFO_BASENAME);
        return str_starts_with(strrev($path), '/') ? $arr[(count($arr) - (1 + $num))] : $arr[(count($arr) - $num)];
    }

    /**
     * 删除文件
     * @param string $path
     * @return boolean
     */
    public function unlink_file(string $path): bool
    {
        $path = $this->dir_replace($path);
        if (file_exists($path)) {
            return unlink($path);
        }
        return false;
    }

    /**
     * 文件操作(复制/移动)
     * @param string $old_path 指定要操作文件路径(需要含有文件名和后缀名)
     * @param string $new_path 指定新文件路径（需要新的文件名和后缀名）
     * @param string $type 文件操作类型
     * @param boolean $overWrite 是否覆盖已存在文件
     * @return boolean
     */
    public function handle_file(string $old_path, string $new_path, string $type = 'copy', bool $overWrite = FALSE): bool
    {
        $old_path = $this->dir_replace($old_path);
        $new_path = $this->dir_replace($new_path);
        if (file_exists($new_path) && $overWrite = FALSE) {
            return FALSE;
        } else if (file_exists($new_path) && $overWrite = TRUE) {
            $this->unlink_file($new_path);
        }

        $aimDir = dirname($new_path);
        $this->create_dir($aimDir);
        return match ($type) {
            'copy' => copy($old_path, $new_path),
            'move' => rename($old_path, $new_path),
            default => false,
        };
    }

    /**
     * 文件夹操作(复制/移动)
     * @param string $old_path 指定要操作文件夹路径
     * @param string $new_path 指定新文件夹路径
     * @param string $type 操作类型
     * @param boolean $overWrite 是否覆盖文件和文件夹
     * @return boolean
     */
    public function handle_dir(string $old_path, string $new_path, string $type = 'copy', bool $overWrite = FALSE): bool
    {
        $new_path = $this->check_path($new_path);
        $old_path = $this->check_path($old_path);
        if (!is_dir($old_path)) return FALSE;

        if (!file_exists($new_path)) $this->create_dir($new_path);

        $dirHandle = opendir($old_path);

        if (!$dirHandle) return FALSE;

        $boolean = TRUE;

        while (FALSE !== ($file = readdir($dirHandle))) {
            if ($file == '.' || $file == '..') continue;

            if (!is_dir($old_path . $file)) {
                $boolean = $this->handle_file($old_path . $file, $new_path . $file, $type, $overWrite);
            } else {
                $this->handle_dir($old_path . $file, $new_path . $file, $type, $overWrite);
            }
        }
        switch ($type) {
            case 'copy':
                closedir($dirHandle);
                return $boolean;
            case 'move':
                closedir($dirHandle);
                return rmdir($old_path);
        }
        return false;
    }

    /**
     * 替换相应的字符
     * @param string $path 路径
     * @return string
     */
    public function dir_replace(string $path): string
    {
        return str_replace('//', '/', str_replace('\\', '/', $path));
    }

    /**
     * 读取指定路径下模板文件
     * @param string $path 指定路径下的文件
     * @return string
     */
    public function get_templates(string $path): string
    {
        $path = $this->dir_replace($path);
        if (file_exists($path)) {
            $fp = fopen($path, 'r');
            $rstr = fread($fp, filesize($path));
            fclose($fp);
            return $rstr;
        } else {
            return '';
        }
    }

    /**
     * 文件重命名
     * @param string $oldname
     * @param string $rename
     * @return bool|void
     */
    public function rename(string $oldname, string $rename)
    {
        if (($rename != $oldname) && is_writable($oldname)) {
            return rename($oldname, $rename);
        }
    }

    /**
     * 获取指定路径下的信息
     * @param string $dir 路径
     */
    public function get_dir_info(string $dir)
    {
        $handle = @opendir($dir);//打开指定目录
        $directory_count = 0;
        $total_size = 0;
        $file_cout=0;
        while (FALSE !== ($file_path = readdir($handle))) {
            if ($file_path != "." && $file_path != "..") {
                //is_dir("$dir/$file_path") ? $sizeResult += $this->get_dir_size("$dir/$file_path") : $sizeResult += filesize("$dir/$file_path");
                $next_path = $dir . '/' . $file_path;
                if (is_dir($next_path)) {
                    $directory_count++;
                    $result_value = self::get_dir_info($next_path);
                    $total_size += $result_value['size'];
                    $file_cout += $result_value['filecount'];
                    $directory_count += $result_value['dircount'];
                } elseif (is_file($next_path)) {
                    $total_size += filesize($next_path);
                    $file_cout++;
                }
            }
        }
        closedir($handle);//关闭指定目录
        $result_value['size'] = $total_size;
        $result_value['filecount'] = $file_cout;
        $result_value['dircount'] = $directory_count;
        return $result_value;
    }

    /**
     * 指定文件编码转换
     * @param string $path 文件路径
     * @param string $input_code 原始编码
     * @param string $out_code 输出编码
     * @return boolean
     */
    public function change_file_code(string $path, string $input_code, string $out_code): bool
    {
        if (is_file($path))//检查文件是否存在,如果存在就执行转码,返回真
        {
            $content = file_get_contents($path);
            $content = mb_convert_encoding($content, $out_code,$input_code);
            $fp = fopen($path, 'w');
            $b = (bool)fputs($fp, $content);
            fclose($fp);
            return $b;
        }
        return FALSE;
    }

    /**
     * 指定目录下指定条件文件编码转换
     * @param string $dirname 目录路径
     * @param string $input_code 原始编码
     * @param string $out_code 输出编码
     * @param boolean $is_all 是否转换所有子目录下文件编码
     * @param string $exts 文件类型
     * @return boolean
     */
    public function change_dir_files_code(string $dirname, string $input_code, string $out_code, bool $is_all = TRUE, string $exts = ''): bool
    {
        if (is_dir($dirname)) {
            $fh = opendir($dirname);
            while (($file = readdir($fh)) !== FALSE) {
                if (strcmp($file, '.') == 0 || strcmp($file, '..') == 0) {
                    continue;
                }
                $filepath = $dirname . '/' . $file;

                if (is_dir($filepath) && $is_all) {
                    $files = $this->change_dir_files_code($filepath, $input_code, $out_code, $is_all, $exts);
                } else {
                    if ($this->get_ext($filepath) == $exts && is_file($filepath)) {
                        $boole = $this->change_file_code($filepath, $input_code, $out_code, $is_all, $exts);
                        if (!$boole) continue;
                    }
                }
            }
            closedir($fh);
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * 列出指定目录下符合条件的文件和文件夹
     * @param string $dirname 路径
     * @param boolean $is_all 是否列出子目录中的文件
     * @param string $exts 需要列出的后缀名文件
     * @param string $sort 数组排序
     */
    public function list_dir_info(string $dirname, bool $is_all = FALSE, string $exts = '', string $sort = 'ASC'): bool|array
    {
        //处理多于的/号
        $new = strrev($dirname);
        if (strpos($new, '/') == 0) {
            $new = substr($new, 1);
        }
        $dirname = strrev($new);

        $sort = strtolower($sort);//将字符转换成小写

        $files = array();

        if (is_dir($dirname)) {
            $fh = opendir($dirname);
            while (($file = readdir($fh)) !== FALSE) {
                if (strcmp($file, '.') == 0 || strcmp($file, '..') == 0) continue;

                $filepath = $dirname . '/' . $file;

                switch ($exts) {
                    case '*':
                        if (is_dir($filepath) && $is_all) {
                            $files = array_merge($files, self::list_dir_info($filepath, $is_all, $exts, $sort));
                        }
                        $files[] = $filepath;
                        break;
                    case 'folder':
                        if (is_dir($filepath) && $is_all) {
                            $files = array_merge($files, self::list_dir_info($filepath, $is_all, $exts, $sort));
                            $files[] = $filepath;
                        } elseif (is_dir($filepath)) {
                            $files[] = $filepath;
                        }
                        break;
                    case 'file':
                        if (is_dir($filepath) && $is_all) {
                            $files = array_merge($files, self::list_dir_info($filepath, $is_all, $exts, $sort));
                        } elseif (is_file($filepath)) {
                            $files[] = $filepath;
                        }
                        break;
                    default:
                        if (is_dir($filepath) && $is_all) {
                            $files = array_merge($files, self::list_dir_info($filepath, $is_all, $exts, $sort));
                        } elseif (preg_match("/\.($exts)/i", $filepath) && is_file($filepath)) {
                            $files[] = $filepath;
                        }
                        break;
                }

                switch ($sort) {
                    case 'asc':
                        sort($files);
                        break;
                    case 'desc':
                        rsort($files);
                        break;
                    case 'nat':
                        natcasesort($files);
                        break;
                }
            }
            closedir($fh);
            return $files;
        } else {
            return FALSE;
        }
    }

    /**
     * 返回指定路径的文件夹信息，其中包含指定路径中的文件和目录
     * @param string $dir
     * @return bool|array
     */
    public function dir_info(string $dir): bool|array
    {
        return scandir($dir);
    }

    /**
     * 判断目录是否为空
     * @param string $dir
     * @return boolean
     */
    public function is_empty(string $dir): bool
    {
        $handle = opendir($dir);
        while (($file = readdir($handle)) !== false) {
            if ($file != '.' && $file != '..') {
                closedir($handle);
                return true;
            }
        }
        closedir($handle);
        return false;
    }

    /**
     * 返回指定文件和目录的信息
     * @param string $file
     * @return array
     */
    public function list_info(string $file): array
    {
        $dir = array();
        $dir['filename'] = basename($file);//返回路径中的文件名部分。
        $dir['pathname'] = realpath($file);//返回绝对路径名。
        $dir['owner'] = fileowner($file);//文件的 user ID （所有者）。
        $dir['inode'] = fileinode($file);//返回文件的 inode 编号。
        $dir['group'] = filegroup($file);//返回文件的组 ID。
        $dir['path'] = dirname($file);//返回路径中的目录名称部分。
        $dir['atime'] = fileatime($file);//返回文件的上次访问时间。
        $dir['ctime'] = filectime($file);//返回文件的上次改变时间。
        $dir['perms'] = fileperms($file);//返回文件的权限。
        $dir['size'] = filesize($file);//返回文件大小。
        $dir['type'] = filetype($file);//返回文件类型。
        $dir['ext'] = is_file($file) ? pathinfo($file, PATHINFO_EXTENSION) : '';//返回文件后缀名
        $dir['mtime'] = filemtime($file);//返回文件的上次修改时间。
        $dir['isDir'] = is_dir($file);//判断指定的文件名是否是一个目录。
        $dir['isFile'] = is_file($file);//判断指定文件是否为常规的文件。
        $dir['isLink'] = is_link($file);//判断指定的文件是否是连接。
        $dir['isReadable'] = is_readable($file);//判断文件是否可读。
        $dir['isWritable'] = is_writable($file);//判断文件是否可写。
        $dir['isUpload'] = is_uploaded_file($file);//判断文件是否是通过 HTTP POST 上传的。
        return $dir;
    }

    /**
     * 返回关于打开文件的信息
     * @param $file
     * 数字下标     关联键名（自 PHP 4.0.6）     说明
     * 0     dev     设备名
     * 1     ino     号码
     * 2     mode     inode 保护模式
     * 3     nlink     被连接数目
     * 4     uid     所有者的用户 id
     * 5     gid     所有者的组 id
     * 6     rdev     设备类型，如果是 inode 设备的话
     * 7     size     文件大小的字节数
     * 8     atime     上次访问时间（Unix 时间戳）
     * 9     mtime     上次修改时间（Unix 时间戳）
     * 10     ctime     上次改变时间（Unix 时间戳）
     * 11     blksize     文件系统 IO 的块大小
     * 12     blocks     所占据块的数目
     */
    public function open_info($file): bool|array
    {
        $file = fopen($file, "r");
        $result = fstat($file);
        fclose($file);
        return $result;
    }

    /**
     * 改变文件和目录的相关属性
     * @param string $file 文件路径
     * @param string $type 操作类型
     * @param string $ch_info 操作信息
     * @return boolean
     */
    public function change_file(string $file, string $type, string $ch_info): bool
    {
        return match ($type) {
            'group' => chgrp($file, $ch_info),  // 改变文件组。
            'mode' => chmod($file, $ch_info),   // 改变文件模式。
            'owner' => chown($file, $ch_info),  // 改变文件所有者。
        };
    }

    /**
     * 取得文件路径信息
     * @param string $path 完整路径
     * @return array|string
     */
    public function get_file_type(string $path): array|string
    {
        // pathinfo() 函数以数组的形式返回文件路径的信息。
        // ---------$file_info = pathinfo($path); echo file_info['extension'];----------//
        // extension取得文件后缀名【pathinfo($path,PATHINFO_EXTENSION)】
        // -----dirname取得文件路径【pathinfo($path,PATHINFO_DIRNAME)】
        // -----basename取得文件完整文件名【pathinfo($path,PATHINFO_BASENAME)】
        // -----filename取得文件名【pathinfo($path,PATHINFO_FILENAME)】
        return pathinfo($path);
    }

    /**
     * 取得上传文件信息
     * @param string $file file属性信息
     * @return array
     */
    public function get_upload_file_info(string $file): array
    {
        $file_info = $_FILES[$file];//取得上传文件基本信息
        $info = array();
        $info['type'] = strtolower(trim(stripslashes(preg_replace("/^(.+?);.*$/", "\\1", $file_info['type'])), '"'));//取得文件类型
        $info['temp'] = $file_info['tmp_name'];//取得上传文件在服务器中临时保存目录
        $info['size'] = $file_info['size'];//取得上传文件大小
        $info['error'] = $file_info['error'];//取得文件上传错误
        $info['name'] = $file_info['name'];//取得上传文件名
        $info['ext'] = $this->get_ext($file_info['name']);//取得上传文件后缀
        return $info;
    }

    /**
     * 设置文件命名规则
     * @param string $type 命名规则
     * @return string
     */
    public function set_file_name(string $type): string
    {
        return match ($type) {
            'hash' => md5(uniqid(mt_rand())),
            'time' => time(),
            default => date($type, time()),
        };
    }

    /**
     * 文件保存路径处理
     * @param $path
     * @return string
     */
    public function check_path($path): string
    {
        return (preg_match('/\/$/', $path)) ? $path : $path . '/';
    }

    public function down_remote_file($url, $save_dir = '', $filename = '', $type = 0): array
    {

        if (trim($url) == '') {
            return array('file_name' => '', 'save_path' => '', 'error' => 1);
        }
        if (trim($save_dir) == '') {
            $save_dir = './';
        }
        if (trim($filename) == '') {//保存文件名
            $ext = strrchr($url, '.');
            //    if($ext!='.gif'&&$ext!='.jpg'){
            //        return array('file_name'=>'','save_path'=>'','error'=>3);
            //    }
            $filename = time() . $ext;
        }
        if (0 !== strrpos($save_dir, '/')) {
            $save_dir .= '/';
        }
        //创建保存目录
        if (!file_exists($save_dir) && !mkdir($save_dir, 0777, true)) {
            return array('file_name' => '', 'save_path' => '', 'error' => 5);
        }
        //获取远程文件所采用的方法 
        if ($type) {
            $ch = curl_init();
            $timeout = 5;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $img = curl_exec($ch);
            curl_close($ch);
        } else {
            ob_start();
            readfile($url);
            $img = ob_get_contents();
            ob_end_clean();
        }
        //$size=strlen($img);
        //文件大小 
        $fp2 = fopen($save_dir . $filename, 'a');

        fwrite($fp2, $img);
        fclose($fp2);
        unset($img, $url);
        return array('file_name' => $filename, 'save_path' => $save_dir . $filename, 'error' => 0);
    }


}
/*
$file = new file();
$file_path = 'C:/Documents and Settings/Administrator/桌面/phpThumb_1.7.10-201104242100-beta';
$files = 'C:/Documents and Settings/Administrator/桌面/phpThumb_1.7.10-201104242100-beta/index.php';
$create_path = 'D:/这是创建的目录/哈哈/爱/的/味道/是/雪儿/给的/';
echo '创建文件夹:create_dir()<br>';
//if($file->create_dir($create_path)) echo '创建目录成功'; else '创建目录失败';
echo '<hr>创建文件:create_file()<br>';
//if($file->create_file($create_path.'创建的文件.txt',true,strtotime('1988-05-04'),strtotime('1988-05-04'))) echo '创建文件成功!'; else echo '创建文件失败!';
echo '<hr>删除非空目录:remove_dir()<br>';
//if($file->remove_dir($file_path,true)) echo '删除非空目录成功!'; else echo '删除非空目录失败!';
echo '<hr>取得文件完整名称(带后缀名):get_basename()<br>';
//echo $file->get_basename($files);
echo '<hr>取得文件后缀名:get_ext()<br>';
//echo $file->get_ext($files);
echo '<hr>取得上N级目录:father_dir()<br>';
//echo $file->father_dir($file_path,3);
echo '<hr>删除文件:unlink_file()<br>';
//if($file->unlink_file($file_path.'/index.php')) echo '删除文件成功!'; else '删除文件失败!';
echo '<hr>操作文件:handle_file()<br>';
//if($file->handle_file($file_path.'/index.php',$create_path.'/index.php','copy',true)) echo '复制文件成功!'; else echo '复制文件失败!';
//if($file->handle_file($file_path.'/index.php', $create_path.'/index.php','move',true)) echo '文件移动成功!'; else echo '文件移动失败!';
echo '<hr>操作文件夹:handle_dir()<br>';
//if($file->handle_dir($file_path,$create_path,'copy',true)) echo '复制文件夹成功!'; else echo '复制文件夹失败!';
//if($file->handle_dir($file_path,$create_path,'move',true)) echo '移动文件夹成功!'; else echo '移动文件夹失败!';
echo '<hr>取得文件夹信息:get_dir_info()<br>';
//print_r($file->get_dir_info($create_path));
echo '<hr>替换统一格式路径:dir_replace()<br>';
//echo $file->dir_replace("c:\d/d\e/d\h");
echo '<hr>取得指定模板文件:get_templtes()<br>';
//echo $file->get_templtes($create_path.'/index.php');
echo '<hr>取得指定条件的文件夹中的文件:list_dir_info()<br>';
//print_r($file->list_dir_info($create_path,true));
echo '<hr>取得文件夹信息:dir_info()<br>';
//print_r($file->dir_info($create_path));
echo '<hr>判断文件夹是否为空:is_empty()<br>';
//if($file->is_empty($create_path)) echo '不为空'; else echo'为空';
echo '<hr>返回指定文件和目录的信息:list_info()<br>';
//print_r($file->list_info($create_path));
echo '<hr>返回关于打开文件的信息:open_info()<br>';
//print_r($file->open_info($create_path.'/index.php'));
echo '<hr>取得文件路径信息:get_file_type()<br>';
//print_r($file->get_file_type($create_path));
 * */

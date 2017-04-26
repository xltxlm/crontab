<?php

/**
 * Created by PhpStorm.
 * User: xialintai
 * Date: 2017/1/3
 * Time: 13:54.
 */

namespace xltxlm\crontab\Unit;

class Tail
{
    /** @var string 需要跟踪的文件 */
    protected $file = '';
    /** @var string 类文件的路径 */
    protected $classFilePath = '';
    /** @var string 特定的错误关键词 */
    protected $errorstr = '';

    /**
     * @return string
     */
    public function getErrorstr(): string
    {
        return $this->errorstr;
    }

    /**
     * @param string $errorstr
     *
     * @return Tail
     */
    public function setErrorstr(string $errorstr): Tail
    {
        $this->errorstr = $errorstr;

        return $this;
    }


    /**
     * @return string
     */
    public function getClassFilePath(): string
    {
        return $this->classFilePath;
    }

    /**
     * @param string $classFilePath
     *
     * @return Tail
     */
    public function setClassFilePath(string $classFilePath): Tail
    {
        $this->classFilePath = strtr($classFilePath, [dirname(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[0]['file']).'/' => '']);

        return $this;
    }

    /**
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * @param string $file
     *
     * @return Tail
     */
    public function setFile(string $file): Tail
    {
        $this->file = $file;

        return $this;
    }
}

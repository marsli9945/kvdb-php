<?php
require_once "DbDrive.php";


class FileDb implements DbDrive
{
    private $path;
    private $idx_fp; // 保存索引文件的具柄
    private $dat_fp;
    private $closed = false;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * 哈希值计算方法
     * @param string $string 键
     * @return int 哈希值
     */
    private function _hash(string $string): int
    {
        $string = substr(md5($string), 0, 8);

        $hash = 0;
        for ($i = 0; $i < 8; $i++)
        {
            $hash += 33 * $hash + ord($string{$i});
        }

        return $hash & 0x7FFFFFFF;
    }

    function open(string $table): int
    {
        $idxPath = $this->path . $table . ".idx";
        $datPath = $this->path . $table . ".dat";

        $init = true;
        $mode = "w + b";
        if (file_exists($idxPath))
        {
            $init = false;
            $mode = "r + b";
        }

        $this->idx_fp = fopen($idxPath, $mode);
        // 索引文件打开失败直接返回
        if (!$this->idx_fp)
        {
            return self::DB_FAILURE;
        }

        // 初始化索引块，共262144个数值为0，长度为4的长整型，大约占1MB
        if ($init)
        {
            $elem = pack("L", 0x00000000);
            for ($i = 0; $i < self::DB_BUCKET_SIZE; $i++)
            {
                fwrite($this->idx_fp, $elem, 4);
            }
        }

        $this->dat_fp = fopen($datPath, $mode);
        if (!$this->dat_fp)
        {
            return self::DB_FAILURE;
        }

        return self::DB_SUCCESS;
    }

    function close(): bool
    {
        if (!$this->closed)
        {
            fclose($this->idx_fp);
            fclose($this->dat_fp);
            $this->closed = true;
        }

        return $this->closed;
    }

    /**
     * 写入本次插入数据的索引块和数据
     * @param int $offset 链表的前一个索引偏移量
     * @param int $idx_off 本次索引块要写入位置的偏移量
     * @param string $block 本次记录数据的索引块
     * @param string $data 要写入的数据
     */
    private function writeData(int $offset, int $idx_off, string $block, string $data): void
    {
        fseek($this->idx_fp, $offset, SEEK_SET);
        fwrite($this->idx_fp, pack("L", $idx_off), 4);

        fseek($this->idx_fp, 0, SEEK_END);
        fwrite($this->idx_fp, $block, self::DB_INDEX_SIZE);

        fseek($this->dat_fp, 0, SEEK_END);
        fwrite($this->dat_fp, $data, strlen($data));
    }

    function insert(string $key, string $data): int
    {
        // 计算索引偏移量
        $offset = ($this->_hash($key) % self::DB_BUCKET_SIZE) * 4;

        // 获取索引文件和数据文件对大小及文件末尾的偏移量
        $idx_off = intval(fstat($this->idx_fp)["size"]);
        $dat_off = intval(fstat($this->dat_fp)["size"]);

        // 键的长度大于最大长度返回错误
        $key_len = strlen($key);
        if ($key_len > self::DB_KEY_SIZE)
        {
            return self::DB_FAILURE;
        }

        // 构建此次需要写入的索引块
        $block = pack("L", 0x00000000);
        $block .= $key;
        $space = self::DB_KEY_SIZE - $key_len;
        for ($i = 0; $i < $space; $i++)
        {
            $block .= pack("C", 0x00);
        }
        $block .= pack("L", $dat_off);
        $block .= pack("L", strlen($data));

        // 获取初始索引块的偏移量部分
        fseek($this->idx_fp, $offset, SEEK_SET);
        $pos = unpack("L", fread($this->idx_fp, 4))[1];

        /**
         * 初始索引块还未使用
         * 直接将文件末尾的偏移量写入索引块的前4位
         * 及记录当前文件末尾为此次索引的位置
         * 在文件末尾出写入构建好的索引块
         */
        if ($pos == 0)
        {
            $this->writeData($offset, $idx_off, $block, $data);
            return self::DB_SUCCESS;
        }

        /**
         * 初始索引块已经被占用
         * 获取调下一个索引块偏移量获取索引
         * 对比key如果找到相同的key标记找到了key
         * 后续直接返回 DB_KEY_EXISTS
         * 否的找出链表末端
         */
        $fond = false;
        $prev = 0;
        while ($pos)
        {
            fseek($this->idx_fp, $pos, SEEK_SET);
            $tmp_block = fread($this->idx_fp, self::DB_INDEX_SIZE);
            $cp_key = substr($tmp_block, 4, self::DB_KEY_SIZE);

            if (!strncmp($key, $cp_key, strlen($key)))
            {
                $fond = true;
                break;
            }

            $prev = $pos;
            $pos = unpack("L", substr($tmp_block, 0, 4))[1];
        }

        // 有重复的key
        if ($fond)
        {
            return self::DB_KEY_EXISTS;
        }

        // 将将文件末尾偏移量写入链表末尾索引的偏移量记录块
        $this->writeData($prev, $idx_off, $block, $data);

        return self::DB_SUCCESS;
    }

    function find(string $key): string
    {
        $offset = ($this->_hash($key) % self::DB_BUCKET_SIZE) * 4;

        fseek($this->idx_fp, $offset, SEEK_SET);
        $pos = unpack("L", fread($this->idx_fp, 4))[1];

        $fond = false;
        $data_off = 0;
        $data_len = 0;
        while ($pos)
        {
            fseek($this->idx_fp, $pos, SEEK_SET);
            $block = fread($this->idx_fp, self::DB_INDEX_SIZE);
            $cp_key = substr($block, 4, self::DB_KEY_SIZE);

            if (!strncmp($key, $cp_key, strlen($key)))
            {
                $data_off = unpack("L", substr($block, self::DB_KEY_SIZE + 4, 4))[1];
                $data_len = unpack("L", substr($block, self::DB_KEY_SIZE + 8, 4))[1];

                $fond = true;
                break;
            }

            $pos = unpack("L", substr($block, 0, 4))[1];
        }

        if (!$fond)
        {
            return "";
        }

        fseek($this->dat_fp, $data_off, SEEK_SET);
        return fread($this->dat_fp, $data_len);
    }

    function delete(string $key): int
    {
        $offset = ($this->_hash($key) % self::DB_BUCKET_SIZE) * 4;

        fseek($this->idx_fp, $offset, SEEK_SET);
        $pos = unpack("L", fread($this->idx_fp, 4))[1];

        $curr = $pos;
        $prev = 0;
        $next = 0;

        $fond = false;
        while ($curr)
        {
            fseek($this->idx_fp, $curr, SEEK_SET);
            $block = fread($this->idx_fp, self::DB_INDEX_SIZE);
            $cp_key = substr($block, 4, self::DB_KEY_SIZE);

            $next = unpack("L", substr($block, 0, 4))[1];

            if (!strncmp($key, $cp_key, strlen($key)))
            {
                $fond = true;
                break;
            }

            $curr = $next;
            $prev = $curr;
        }

        if (!$fond)
        {
            return self::DB_FAILURE;
        }

        if ($prev == 0)
        {
            fseek($this->idx_fp, $offset, SEEK_SET);
            fwrite($this->idx_fp, pack("L", $next), 4);
        } else
        {
            fseek($this->idx_fp, $prev, SEEK_SET);
            fwrite($this->idx_fp, pack("L", $next), 4);
        }

        return self::DB_SUCCESS;
    }
}
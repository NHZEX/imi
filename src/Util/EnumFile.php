<?php

namespace Imi\Util;

use Exception;
use Imi\Util\File\FileEnumItem;
use Traversable;
use function array_flip;
use function array_map;
use function closedir;
use function implode;
use function is_dir;
use function is_null;
use function opendir;
use function pathinfo;
use function preg_match;
use function readdir;

class EnumFile implements \IteratorAggregate
{
    private string  $dirPath;

    private ?string $pattern;

    private ?string $extensionNamesPattern = null;

    public function __construct(string $dirPath, ?string $pattern = null, array $extensionNames = [])
    {
        if (!empty($extensionNames))
        {
            $this->extensionNamesPattern = '/\\.(' . implode('|', $extensionNames) . ')$/';
        }
        $this->dirPath = $dirPath;
        $this->pattern = $pattern;
    }

    /**
     * 枚举文件，支持自定义中断进入下一级目录.
     *
     * @return \Generator|array
     */
    protected function enumFile()
    {
        if (!is_dir($this->dirPath))
        {
            return [];
        }
        $dh = opendir($this->dirPath);
        if (false === $dh)
        {
            return [];
        }
        while ($file = readdir($dh))
        {
            if ('.' !== $file && '..' !== $file)
            {
                $item = new FileEnumItem($this->dirPath, $file);
                $fullPath = $item->getFullPath();
                if (null !== $this->pattern && !preg_match($this->pattern, $fullPath))
                {
                    continue;
                }
                if (null === $this->extensionNamesPattern || preg_match($this->extensionNamesPattern, $item->getFileName()))
                {
                    yield $item;
                }
                if ($item->getContinue() && is_dir($fullPath))
                {
                    $it = clone $this;
                    $it->dirPath = $fullPath;
                    yield from $it;
                }
            }
        }
        closedir($dh);
    }

    public function getIterator(): Traversable
    {
        return $this->enumFile();
    }
}

<?php

declare(strict_types=1);

namespace Imi\Util;

use function array_flip;
use function closedir;
use function is_dir;
use function opendir;
use function preg_match;
use function readdir;

class EnumFile implements \IteratorAggregate
{
    private string  $dirPath;

    private ?string $pattern;

    private array $extensionNamesMap = [];

    public function __construct(string $dirPath, ?string $pattern = null, array $extensionNames = [])
    {
        if (!empty($extensionNames))
        {
            $this->extensionNamesMap = array_flip($extensionNames);
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
                $item = new \SplFileInfo($this->dirPath . \DIRECTORY_SEPARATOR . $file);
                $fullPath = $item->getPathname();
                if (null !== $this->pattern && !preg_match($this->pattern, $fullPath))
                {
                    continue;
                }
                if ($item->isDir())
                {
                    $it = clone $this;
                    $it->dirPath = $fullPath;
                    yield from $it;
                }
                elseif (empty($this->extensionNamesMap) || isset($this->extensionNamesMap[$item->getExtension()]))
                {
                    yield $item;
                }
            }
        }
        closedir($dh);
    }

    /**
     * @return iterable<\SplFileInfo>
     */
    public function getIterator()
    {
        return $this->enumFile();
    }
}

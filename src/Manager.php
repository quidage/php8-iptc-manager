<?php

declare(strict_types=1);

namespace iBudasov\Iptc;

use iBudasov\Iptc\Domain\Binary;
use iBudasov\Iptc\Domain\FileSystem;
use iBudasov\Iptc\Domain\Image;
use iBudasov\Iptc\Domain\Tag;

class Manager
{
    private const SUPPORTED_FILE_TYPES = ['jpg', 'jpeg', 'pjpeg'];

    /**
     * @var string
     */
    private $pathToFile;

    /**
     * @var FileSystem
     */
    private $fileSystem;

    /**
     * @var Image
     */
    private $image;

    /**
     * @var Binary
     */
    private $binary;

    /**
     * @var Tag[]
     */
    private $tags;

    /**
     * @param FileSystem $fileSystem
     * @param Image      $image
     * @param Binary     $binary
     */
    public function __construct(FileSystem $fileSystem, Image $image, Binary $binary)
    {
        $this->fileSystem = $fileSystem;
        $this->image = $image;
        $this->binary = $binary;
    }

    /**
     * @param string $pathToFile
     */
    public function setPathToFile(string $pathToFile): void
    {
        $this->checkIfFileExists($pathToFile);

        $this->checkIfFileTypeIsSupported($pathToFile);

        $this->pathToFile = $pathToFile;

        $this->tags = $this->image->getIptcTags($pathToFile);
    }

    /**
     * @return Tag[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param string $tagCode
     *
     * @return Tag|null
     */
    public function getTag(string $tagCode): ?Tag
    {
        foreach ($this->tags as $iptcTag) {
            if ($iptcTag->getCode() == $tagCode) {
                return $iptcTag;
            }
        }

        return null;
    }

    /**
     * @throws \Exception
     */
    public function write(): void
    {
        $binaryString = '';
        foreach ($this->tags as $tag) {
            $binaryString .= $this->binary->createBinaryStringFromTag($tag);
        }

        $updatedBinaryFileContent = $this->image->writeIptcTags($this->pathToFile, $binaryString);
        if (empty($updatedBinaryFileContent)) {
            throw new \Exception('Can not write IPTC tags to file: '.$this->pathToFile);
        }

        $this->fileSystem->deleteFile($this->pathToFile);

        $this->fileSystem->createFileWithBinaryContent($this->pathToFile, $updatedBinaryFileContent);
    }

    /**
     * @param string $pathToFile
     */
    private function checkIfFileTypeIsSupported(string $pathToFile): void
    {
        $fileExtension = \pathinfo($pathToFile, PATHINFO_EXTENSION);
        if (!\in_array($fileExtension, self::SUPPORTED_FILE_TYPES)) {
            throw new \InvalidArgumentException(
                'Supported file types are: ' . \json_encode(self::SUPPORTED_FILE_TYPES)
            );
        }
    }

    /**
     * @param string $pathToFile
     */
    private function checkIfFileExists(string $pathToFile): void
    {
        if (false === $this->fileSystem->isFile($pathToFile)) {
            throw new \InvalidArgumentException('File not found');
        }
    }
}

<?php
Namespace Archive
{
    Class Zip
    {
        /**
         * Zip a folder (include itself).
         * Usage:
         *   Zip::compress('/path/to/sourceDir', '/path/to/out.zip');
         *
         * @param string $sourcePath    Path of directory to be zip.
         * @param string $outZipPath    Path of output zip file.
         * @param bool   $useSourceDir  Use source dir as output path
         */
        public static function compress($sourcePath, $outZipPath = '', $useSourceDir = false)
        {
            $sourcePath .= ('/' != substr($sourcePath, -1)
                ? '/'
                : ''
            );

            if ($useSourceDir) $outZipPath = dirname($sourcePath) . '/' . $outZipPath;

            $pathInfo = pathInfo($sourcePath);
            $parentPath = $pathInfo['dirname'];
            $dirName = $pathInfo['basename'];

            $z = New \ZipArchive();
            $z->open($outZipPath, \ZIPARCHIVE::CREATE);
            $z->addEmptyDir($dirName);
            self::archiveDirectory($sourcePath, $z, strlen("$parentPath/"));
            $z->close();
        }

        /**
         * Add files and sub-directories in a folder to zip file.
         *
         * @param string     $folder
         * @param ZipArchive $zipFile
         * @param int        $excLen Number of text to be excluded from the file path.
         */
        private static function archiveDirectory ($folder, &$zipFile, $excLen)
        {
            $handle = opendir ($folder);
            while (false !== $f = readdir ($handle))
            {
                if ($f != '.' && $f != '..')
                {
                    $filePath = "$folder/$f";
                    // Remove prefix from file path before add to zip.
                    $localPath = substr ($filePath, $excLen);
                    if (is_file ($filePath))
                    {
                        $zipFile->addFile ($filePath, $localPath);
                    }
                    else if (is_dir ($filePath))
                    {
                        // Add sub-directory.
                        $zipFile->addEmptyDir ($localPath);
                        self::archiveDirectory ($filePath, $zipFile, $excLen);
                    }
                }
            }
            closedir ($handle);
        }
    }
}

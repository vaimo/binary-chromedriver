<?php

namespace Lbaey;

use Composer\Config;
use Composer\Factory;
use Composer\Package\Package;
use Composer\Script\Event;
use Composer\Util\Filesystem;
use Composer\Util\RemoteFilesystem;

class ChromeDriverInstall
{
    public static function postInstall(Event $event)
    {
        if (stripos(PHP_OS, 'win') === 0) {
            $platform = 'Windows';
            $chromeDriverExecutableFileName = 'chromedriver.exe';
            $chromeDriverFileVersion = "win32";
        } elseif (stripos(PHP_OS, 'darwin') === 0) {
            $platform = 'Mac OS X';
            $chromeDriverExecutableFileName = 'chromedriver';
            $chromeDriverFileVersion = "mac64";
        } elseif (stripos(PHP_OS, 'linux') === 0) {
            $platform = 'Linux';
            $chromeDriverExecutableFileName = 'chromedriver';
            if (PHP_INT_SIZE === 8) {
                $chromeDriverFileVersion = "linux64";
            } else {
                $chromeDriverFileVersion = "linux32";
            }

        } else {
            $event->getIO()->writeError('Could not guess your platform, download chromedriver manually.');

            return;
        }

        /** @var Config $config */
        $config = $event->getComposer()->getConfig();
        /** @var Package $installedPackage */
        $installedPackage = $event->getComposer()->getPackage();
        $version = preg_replace('@.0.0$@', '', $installedPackage->getVersion());
        $event->getIO()->write(sprintf(
            "Downloading Chromedriver version %s for %s",
            $version,
            $platform
        ));
        $chromeDriverOriginUrl = "https://chromedriver.storage.googleapis.com";
        $chromeDriverRemoteFile = $version . "/chromedriver_" . $chromeDriverFileVersion . ".zip";
        $event->getIO()->write($chromeDriverOriginUrl . $chromeDriverRemoteFile);

        /** @var RemoteFilesystem $remoteFileSystem */
        $remoteFileSystem = Factory::createRemoteFilesystem($event->getIO(), $config);

        $fs = new Filesystem();
        $fs->ensureDirectoryExists($config->get('bin-dir'));

        $chromeDriverArchiveFileName = $config->get('bin-dir') . DIRECTORY_SEPARATOR . 'chromedriver_' . $chromeDriverFileVersion . ".zip";
        $remoteFileSystem->copy($chromeDriverOriginUrl, $chromeDriverOriginUrl . '/' . $chromeDriverRemoteFile, $chromeDriverArchiveFileName);

        $archive = new \ZipArchive();
        $archive->open($chromeDriverArchiveFileName);
        $archive->extractTo($config->get('bin-dir'));

        if ($platform !== 'Windows') {
            chmod($config->get('bin-dir') . DIRECTORY_SEPARATOR . $chromeDriverExecutableFileName, 0755);
        }

        $fs->unlink($chromeDriverArchiveFileName);
    }
}
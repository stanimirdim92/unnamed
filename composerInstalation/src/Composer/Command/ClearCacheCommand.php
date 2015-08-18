<?php











namespace Composer\Command;

use Composer\Cache;
use Composer\Factory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;




class ClearCacheCommand extends Command
{
protected function configure()
{
$this
->setName('clear-cache')
->setAliases(array('clearcache'))
->setDescription('Clears composer\'s internal package cache.')
->setHelp(<<<EOT
The <info>clear-cache</info> deletes all cached packages from composer's
cache directory.
EOT
)
;
}

protected function execute(InputInterface $input, OutputInterface $output)
{
$config = Factory::createConfig();
$io = $this->getIO();

$cachePaths = array(
'cache-dir' => $config->get('cache-dir'),
'cache-files-dir' => $config->get('cache-files-dir'),
'cache-repo-dir' => $config->get('cache-repo-dir'),
'cache-vcs-dir' => $config->get('cache-vcs-dir'),
);

foreach ($cachePaths as $key => $cachePath) {
$cachePath = realpath($cachePath);
if (!$cachePath) {
$io->write("<info>Cache directory does not exist ($key): $cachePath</info>");

return;
}
$cache = new Cache($io, $cachePath);
if (!$cache->isEnabled()) {
$io->write("<info>Cache is not enabled ($key): $cachePath</info>");

return;
}

$io->write("<info>Clearing cache ($key): $cachePath</info>");
$cache->gc(0, 0);
}

$io->write('<info>All caches cleared.</info>');
}
}

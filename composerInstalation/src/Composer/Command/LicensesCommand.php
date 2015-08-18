<?php











namespace Composer\Command;

use Composer\Json\JsonFile;
use Composer\Package\Version\VersionParser;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Package\PackageInterface;
use Composer\Repository\RepositoryInterface;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;




class LicensesCommand extends Command
{
protected function configure()
{
$this
->setName('licenses')
->setDescription('Show information about licenses of dependencies')
->setDefinition(array(
new InputOption('format', 'f', InputOption::VALUE_REQUIRED, 'Format of the output: text or json', 'text'),
new InputOption('no-dev', null, InputOption::VALUE_NONE, 'Disables search in require-dev packages.'),
))
->setHelp(<<<EOT
The license command displays detailed information about the licenses of
the installed dependencies.

EOT
)
;
}

protected function execute(InputInterface $input, OutputInterface $output)
{
$composer = $this->getComposer();

$commandEvent = new CommandEvent(PluginEvents::COMMAND, 'licenses', $input, $output);
$composer->getEventDispatcher()->dispatch($commandEvent->getName(), $commandEvent);

$root = $composer->getPackage();
$repo = $composer->getRepositoryManager()->getLocalRepository();

$versionParser = new VersionParser;

if ($input->getOption('no-dev')) {
$packages = $this->filterRequiredPackages($repo, $root);
} else {
$packages = $this->appendPackages($repo->getPackages(), array());
}

ksort($packages);

switch ($format = $input->getOption('format')) {
case 'text':
$output->writeln('Name: <comment>'.$root->getPrettyName().'</comment>');
$output->writeln('Version: <comment>'.$versionParser->formatVersion($root).'</comment>');
$output->writeln('Licenses: <comment>'.(implode(', ', $root->getLicense()) ?: 'none').'</comment>');
$output->writeln('Dependencies:');

$table = $this->getHelperSet()->get('table');
$table->setLayout(TableHelper::LAYOUT_BORDERLESS);
$table->setHorizontalBorderChar('');
foreach ($packages as $package) {
$table->addRow(array(
$package->getPrettyName(),
$versionParser->formatVersion($package),
implode(', ', $package->getLicense()) ?: 'none',
));
}
$table->render($output);
break;

case 'json':
foreach ($packages as $package) {
$dependencies[$package->getPrettyName()] = array(
'version' => $versionParser->formatVersion($package),
'license' => $package->getLicense(),
);
}

$output->writeln(JsonFile::encode(array(
'name' => $root->getPrettyName(),
'version' => $versionParser->formatVersion($root),
'license' => $root->getLicense(),
'dependencies' => $dependencies,
)));
break;

default:
throw new \RuntimeException(sprintf('Unsupported format "%s".  See help for supported formats.', $format));
}
}







private function filterRequiredPackages(RepositoryInterface $repo, PackageInterface $package, $bucket = array())
{
$requires = array_keys($package->getRequires());

$packageListNames = array_keys($bucket);
$packages = array_filter(
$repo->getPackages(),
function ($package) use ($requires, $packageListNames) {
return in_array($package->getName(), $requires) && !in_array($package->getName(), $packageListNames);
}
);

$bucket = $this->appendPackages($packages, $bucket);

foreach ($packages as $package) {
$bucket = $this->filterRequiredPackages($repo, $package, $bucket);
}

return $bucket;
}








public function appendPackages(array $packages, array $bucket)
{
foreach ($packages as $package) {
$bucket[$package->getName()] = $package;
}

return $bucket;
}
}
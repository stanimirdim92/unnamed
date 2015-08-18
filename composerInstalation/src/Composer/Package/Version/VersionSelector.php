<?php











namespace Composer\Package\Version;

use Composer\DependencyResolver\Pool;
use Composer\Package\PackageInterface;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Dumper\ArrayDumper;






class VersionSelector
{
private $pool;

private $parser;

public function __construct(Pool $pool)
{
$this->pool = $pool;
}









public function findBestCandidate($packageName, $targetPackageVersion = null)
{
$constraint = $targetPackageVersion ? $this->getParser()->parseConstraints($targetPackageVersion) : null;
$candidates = $this->pool->whatProvides($packageName, $constraint, true);

if (!$candidates) {
return false;
}


 $package = reset($candidates);
foreach ($candidates as $candidate) {
if (version_compare($package->getVersion(), $candidate->getVersion(), '<')) {
$package = $candidate;
}
}

return $package;
}
















public function findRecommendedRequireVersion(PackageInterface $package)
{
$version = $package->getVersion();
if (!$package->isDev()) {
return $this->transformVersion($version, $package->getPrettyVersion(), $package->getStability());
}

$loader = new ArrayLoader($this->getParser());
$dumper = new ArrayDumper();
$extra = $loader->getBranchAlias($dumper->dump($package));
if ($extra) {
$extra = preg_replace('{^(\d+\.\d+\.\d+)(\.9999999)-dev$}', '$1.0', $extra, -1, $count);
if ($count) {
$extra = str_replace('.9999999', '.0', $extra);

return $this->transformVersion($extra, $extra, 'dev');
}
}

return $package->getPrettyVersion();
}

private function transformVersion($version, $prettyVersion, $stability)
{

 
 $semanticVersionParts = explode('.', $version);
$op = '~';


 if (count($semanticVersionParts) == 4 && preg_match('{^0\D?}', $semanticVersionParts[3])) {

 if ($semanticVersionParts[0] === '0') {
if ($semanticVersionParts[1] === '0') {
$semanticVersionParts[3] = '*';
} else {
$semanticVersionParts[2] = '*';
unset($semanticVersionParts[3]);
}
$op = '';
} else {
unset($semanticVersionParts[2], $semanticVersionParts[3]);
}
$version = implode('.', $semanticVersionParts);
} else {
return $prettyVersion;
}


 if ($stability != 'stable') {
$version .= '@'.$stability;
}


 return $op.$version;
}

private function getParser()
{
if ($this->parser === null) {
$this->parser = new VersionParser();
}

return $this->parser;
}
}

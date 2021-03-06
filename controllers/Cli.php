<?php
/**
 * @author John <john@paycoin.com>
 * @license http://opensource.org/licenses/MIT The MIT License (MIT)
 */
namespace controllers;
use lib\PaycoinDb;
use lib\PaycoinRPC;

/**
 * Class Cli
 * @package controllers
 */
class Cli extends Controller {

	const LOCK_FILE = "/tmp/clibuildDatabase2.lock";
	const NETWORK_LOCK_FILE = "/tmp/getNetworkInfo.lock";

	public function getNetworkInfo() {


		if (!$this->tryLock(self::NETWORK_LOCK_FILE)) {
			die("Already running.\n");
		}

		$paycoinDb = new PaycoinDb();
		$paycoinDb->updateNetworkInfo();

	}

	public function buildDatabase() {


		if (!$this->tryLock(self::LOCK_FILE)) {
			die("Already running.\n");
		}
		register_shutdown_function('unlink', self::LOCK_FILE);

		echo 'Building Database' . PHP_EOL;

		$paycoinRPC = new PaycoinRPC();
		$paycoinDb = new PaycoinDb();

		$startBlockHeight = $paycoinDb->getLastBlockInDb();
		$startBlockHeight = (int)$startBlockHeight;


		$endBlockHeight = $paycoinRPC->getBlockCount();

		if ($startBlockHeight == $endBlockHeight) {
			echo "Caught up.  Last block was $endBlockHeight" . PHP_EOL;
			return;
		} else {
			echo "Catching up with blockchain  $startBlockHeight => $endBlockHeight" . PHP_EOL;
		}

		//@todo move this...
		$startBlockHeight++;
		$paycoinDb->buildDb($startBlockHeight, $endBlockHeight);

		echo "Complete" . PHP_EOL;

	}

	public function buildWalletDatabase() {

		$paycoinDb = new PaycoinDb();
		echo "Building wallet database" . PHP_EOL;
		$paycoinDb->buildWalletDb();

	}

	public function buildRichList() {

		$paycoinDb = new PaycoinDb();
		echo "Building rich list" . PHP_EOL;
		$paycoinDb->buildRichList();

	}

	private function tryLock($lockFile) {


		if (@symlink("/proc/" . getmypid(), $lockFile) !== FALSE) # the @ in front of 'symlink' is to suppress the NOTICE you get if the LOCK_FILE exists
			return true;

		# link already exists
		# check if it's stale
		if (is_link($lockFile) && !is_dir($lockFile)) {
			unlink($lockFile);
			# try to lock again
			return $this->tryLock($lockFile);
		}

		return false;
	}



} 
<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace DebugKit\Panel;

use Cake\Controller\Controller;
use DebugKit\DebugPanel;
use DebugKit\Database\Log\DebugLog;
use Cake\Datasource\ConnectionManager;
use Cake\Event\Event;

/**
 * Provides debug information on the SQL logs and provides links to an ajax explain interface.
 *
 */
class SqlLogPanel extends DebugPanel {

/**
 * Minimum number of Rows Per Millisecond that must be returned by a query before an explain
 * is done.
 *
 * @var integer
 */
	public $slowRate = 20;

/**
 * Loggers connected
 *
 * @var array
 */
	protected $_loggers = [];

/**
 * Startup hook - configures logger.
 *
 * This will unfortunately build all the connections, but they
 * won't connect until used.
 *
 * @param \Cake\Event\Event $event The event.
 * @return array
 */
	public function startup(Controller $controller) {
		$configs = ConnectionManager::configured();
		foreach ($configs as $name) {
			$connection = ConnectionManager::get($name);
			$logger = null;
			if ($connection->logQueries()) {
				$logger = $connection->logger();
			}

			$spy = new DebugLog($logger, $name);
			$this->_loggers[] = $spy;
			$connection->logQueries(true);
			$connection->logger($spy);
		}
	}

/**
 * Gets the connection names that should have logs + dumps generated.
 *
 * @param \Cake\Controller\Controller $contoller The controller.
 * @return array
 */
	public function beforeRender(Controller $controller) {
		return [
			'loggers' => $this->_loggers,
			'threshold' => $this->slowRate,
		];
	}

/**
 * Gets the connection names that should have logs + dumps generated.
 *
 * @param \Cake\Event\Event $event The event.
 * @return array
 */
	public function initialize(Event $event) {
		$this->startup($event->subject());
	}

/**
 * Stores the data this panel wants.
 *
 * @param \Cake\Event\Event $event The event.
 * @return array
 */
	public function shutdown(Event $event) {
		$this->_data = $this->beforeRender($event->subject());
	}
}
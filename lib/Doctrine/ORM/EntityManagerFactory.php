<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM;

use Doctrine\DBAL\Connection;
use Doctrine\Common\EventManager;

/**
 * The EntityManagerFactory is a simple factory object that
 * encapsulates the logic to create EntityManagers
 *
 * @since   2.5
 * @author  Guido Contreras Woda <guiwoda@gmail.com>
 *
 * @todo Dependency Injection of the DriverManager
 */
class EntityManagerFactory 
{
	public function create($conn, Configuration $config, EventManager $eventManager = null)
	{
		if ( ! $config->getMetadataDriverImpl()) {
			throw ORMException::missingMappingDriverImpl();
		}

		switch (true) {
			case (is_array($conn)):
				$conn = \Doctrine\DBAL\DriverManager::getConnection(
					$conn, $config, ($eventManager ?: new EventManager())
				);
				break;

			case ($conn instanceof Connection):
				if ($eventManager !== null && $conn->getEventManager() !== $eventManager) {
					throw ORMException::mismatchedEventManager();
				}
				break;

			default:
				throw new \InvalidArgumentException("Invalid argument: " . $conn);
		}

		return new EntityManager($conn, $config, $conn->getEventManager());
	}
}
